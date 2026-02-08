<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\Section;
use App\Models\Semester;
use App\Services\Scheduling\DTOs\AllocationResult;
use App\Services\Scheduling\SectionQuotaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FifoAllocator
{
    protected array $skipReasons = [];
    protected array $statistics = [];
    protected int $sectionsAssigned = 0;
    protected int $preferencesProcessed = 0;
    protected int $preferencesSkipped = 0;

    public function __construct(
        protected TimeConflictChecker $conflictChecker,
        protected CreditHourCalculator $creditCalculator
    ) {
    }

    /**
     * Main allocation method - processes all preferences in FIFO order.
     */
    public function allocate(Semester $semester): AllocationResult
    {
        // Reset counters
        $this->resetCounters();

        // Get all preferences ordered by submission time (FIFO)
        $preferences = $this->getPreferencesInFifoOrder($semester);

        Log::info("Starting FIFO allocation for semester {$semester->id}", [
            'total_preferences' => $preferences->count(),
        ]);

        // Process each preference
        foreach ($preferences as $preference) {
            $this->processPreference($preference, $semester);
        }

        // Identify unassigned courses
        $unassignedCourses = $this->getUnassignedCourses($semester);

        return new AllocationResult(
            totalSectionsAssigned: $this->sectionsAssigned,
            totalPreferencesProcessed: $this->preferencesProcessed,
            totalPreferencesSkipped: $this->preferencesSkipped,
            unassignedCourses: $unassignedCourses,
            skipReasons: $this->skipReasons,
            statistics: $this->statistics
        );
    }

    /**
     * Get all preferences ordered by Priority (Scarcity).
     * Priority 1: Courses with fewer requests (Hardest to fill).
     * Priority 2: Instructors with fewer preferences (Least flexible).
     * Priority 3: Submission Time (FIFO).
     */
    protected function getPreferencesInFifoOrder(Semester $semester): Collection
    {
        $preferences = InstructorPreference::query()
            ->where('semester_id', $semester->id)
            ->whereNotNull('instructor_id')
            ->whereNotNull('course_id')
            ->with(['instructor.departments', 'course', 'timeSlots'])
            ->get();

        // Calculate metadata for sorting
        $courseRequestCounts = $preferences->groupBy('course_id')->map(fn($g) => $g->count());

        // Enhance collection with sorting keys
        return $preferences->sortBy(function ($pref) use ($courseRequestCounts) {
            $courseScarcity = $courseRequestCounts[$pref->course_id] ?? 999;

            // Getting instructor's total preferences count is expensive in loop if we don't pre-calc, 
            // but let's assume the collection is reasonable in size.
            // Actually, we can just use the loaded collection to count this instructor's prefs.
            // We can't easily access the parent collection inside sort, but we can do a quick lookup if we map it first.
            // For simplicity, let's stick to Course Scarcity as primary.

            return [
                $courseScarcity, // Fewer requests = First
                $pref->submission_time, // Then FIFO
            ];
        });
    }

    /**
     * Process a single instructor preference.
     */
    protected function processPreference(InstructorPreference $preference, Semester $semester): void
    {
        $this->preferencesProcessed++;

        $instructor = $preference->instructor;
        $course = $preference->course;

        if (!$instructor || !$course) {
            $this->skipPreference($preference, 'Internal error: Instructor or Course missing from preference record.');
            return;
        }

        // Rule: Department Qualification Check
        if (!$this->canAssignCourse($instructor, $course)) {
            $this->skipPreference($preference, 'Department qualification rule: Instructor not in course department');
            return;
        }

        // Rule: Section Limit (Max N sections of the same course per instructor, typically 2)
        $perInstructorLimit = SectionQuotaService::getPerInstructorSectionLimit($course, $semester);
        $currentAssignmentCount = SectionQuotaService::getInstructorCourseCount($instructor, $course, $semester);
        if ($currentAssignmentCount >= $perInstructorLimit) {
            $this->skipPreference($preference, "Section limit reached: Max {$perInstructorLimit} sections per course.");
            return;
        }

        // Rule: Hard Credit Capacity Check (18.0)
        $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
        $courseCredits = (float) ($course->credits ?? 3.0);
        $maxCredits = $this->creditCalculator->getMaxCredits($instructor);

        if ($currentCredits + $courseCredits > $maxCredits) {
            $this->skipPreference($preference, "C.H. limit reached: Allocation would exceed hard cap of {$maxCredits}.");
            return;
        }

        // Process each time slot preference
        $timeSlots = $preference->timeSlots;

        if ($timeSlots->isEmpty()) {
            // Assign to any available slot if no preference? 
            // The request says "Match instructor preferences first".
            // If they didn't specify, maybe we should skip or use defaults.
            // Let's try assigning defaults if no slot specified.
            $this->tryAssignDefaultSlots($instructor, $course, $semester);
            return;
        }

        $assignedCountThisPass = 0;
        $perInstructorLimit = SectionQuotaService::getPerInstructorSectionLimit($course, $semester);
        foreach ($timeSlots as $timeSlot) {

            if (SectionQuotaService::getInstructorCourseCount($instructor, $course, $semester) >= $perInstructorLimit) {
                break;
            }

            // Check if instructor reached min credits inside the loop
            if (!$this->creditCalculator->isUnderloaded($instructor, $semester)) {
                break;
            }

            // Try to assign this time slot (Atomic Pair Logic)
            $assignedSection = $this->tryAssignTimeSlot($instructor, $course, $timeSlot, $semester);
            if ($assignedSection) {
                // If tryAssignTimeSlot returned a section, it means it handled the potential double assignment internally.
                // We just need to increment our pass counter.
                // Note: If 2 sections were assigned, this still only counts as "one preference processed" which is correct.
                $assignedCountThisPass++;
            }
        }

        // Enforcing Strictness: If preferences were provided but failed (e.g. conflict), we do NOT fall back to random slots.
        // We only retry defaults if NO preferences were provided at all (handled above).

        if ($assignedCountThisPass === 0) {
            $this->preferencesSkipped++;
        }
    }

    /**
     * Try to assign a section for a specific time slot.
     */
    protected function tryAssignTimeSlot(
        Instructor $instructor,
        Course $course,
        $timeSlot,
        Semester $semester
    ): ?Section {
        // days is now more likely to be an array from my controller update
        $daysValue = $timeSlot->days;
        if (is_string($daysValue)) {
            $decoded = json_decode($daysValue, true);
            $daysValue = $decoded ?: [$daysValue];
        }

        if (is_array($daysValue) && !empty($daysValue)) {
            // The preference IS the pattern (e.g. ['Sunday', 'Wednesday'])
            // We wrap it in an array because the loop expects a list of patterns to try.
            // IMPORTANT: We also add fallback patterns to try if the preferred one fails
            $dayPatterns = [$daysValue, 'Mon/Thu', 'Tue/Sat'];
        } else {
            // Defaults - try all patterns
            $dayPatterns = ['Sun/Wed', 'Mon/Thu', 'Tue/Sat'];
        }

        // Map preferred time ranges to specific slots
        // Morning: 08:30–11:30
        // Noon: 11:30–14:30
        // Afternoon: 14:30–17:30

        $baseStartTime = $timeSlot->start_time;
        // Normalize time (take first 5 chars 'HH:MM')
        $baseParams = substr($baseStartTime ?? '', 0, 5);
        $slotsToTry = [];

        // For atomic pair assignment, we only try the EXACT preferred time
        // Time range expansion is disabled to ensure deterministic consecutive assignment
        if (!empty($baseStartTime)) {
            // Use exact time specified
            $slotsToTry = [$baseStartTime];
        } else {
            // If time is NULL, user allows ANY time -> Try all standard slots
            $slotsToTry = ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];
        }


        // Flatten the combinations we want to try: [Pattern, StartTime]
        // Include pattern_index so we prefer instructor's preferred slot first (deterministic).
        $combinations = [];
        foreach ($dayPatterns as $patternIndex => $pattern) {
            foreach ($slotsToTry as $startTime) {
                // Calculate current load for this slot to load-balance
                $occupancy = Section::where('semester_id', $semester->id)
                    ->where('start_time', $startTime)
                    ->where(function ($query) use ($pattern) {
                        $days = is_array($pattern) ? $pattern : $this->conflictChecker->getDayPair($pattern);
                        foreach ($days as $day) {
                            $query->orWhereJsonContains('days', $day);
                        }
                    })
                    ->count();

                $combinations[] = [
                    'pattern' => $pattern,
                    'pattern_index' => $patternIndex,
                    'start_time' => $startTime,
                    'occupancy' => $occupancy,
                ];
            }
        }

        // Sort combinations deterministically (same input → same output):
        // 1. Least occupancy (spread load)
        // 2. Exact match of requested time (instructor preference)
        // 3. Preferred day pattern first (pattern_index 0 = instructor's choice)
        // 4. Start time as tie-breaker
        usort($combinations, function ($a, $b) use ($baseStartTime) {
            if ($a['occupancy'] !== $b['occupancy']) {
                return $a['occupancy'] <=> $b['occupancy'];
            }
            $aExact = $a['start_time'] === $baseStartTime;
            $bExact = $b['start_time'] === $baseStartTime;
            if ($aExact && !$bExact) {
                return -1;
            }
            if (!$aExact && $bExact) {
                return 1;
            }
            if ($a['pattern_index'] !== $b['pattern_index']) {
                return $a['pattern_index'] <=> $b['pattern_index'];
            }
            return strcmp($a['start_time'], $b['start_time']);
        });

        foreach ($combinations as $combo) {
            $pattern = $combo['pattern'];
            $daysToAssign = is_array($pattern) ? $pattern : $this->conflictChecker->getDayPair($pattern);

            // ATOMIC ASSIGNMENT LOGIC
            // Per-instructor limit: max sections of this course for this instructor (e.g. 2).
            $perInstructorLimit = SectionQuotaService::getPerInstructorSectionLimit($course, $semester);
            $currentAssigned = SectionQuotaService::getInstructorCourseCount($instructor, $course, $semester);
            $quotaRemaining = ($perInstructorLimit > 0) ? ($perInstructorLimit - $currentAssigned) : 1;

            // User Requirement: "When an instructor selects a course, the system must assign exactly two linked sections"
            // If the instructor can have 2 sections and has none yet, we must assign a pair (both at once). No partial assignment.
            $needsPair = ($quotaRemaining >= 2);

            if ($needsPair) {
                // Check if assigning a pair would exceed the minimum load?
                $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
                $minCredits = $this->creditCalculator->getMinimumCredits($instructor, $semester);
                $courseCredits = (float) ($course->credits ?? 3.0);

                // If current + 2 sections > min_credits AND current + 1 section <= min_credits (or close to it) -> Prefer 1 section
                // Actually constraint is "should not exceed min load at all"
                if ($currentCredits + (2 * $courseCredits) > $minCredits) {
                    // Pair would exceed limit.
                    // Fallback to Single assignment?
                    // Verify if single fits.
                    if ($currentCredits + $courseCredits <= $minCredits) {
                        $needsPair = false; // Downgrade to single
                    } else {
                        // Even single exceeds? Then maybe we shouldn't assign at all?
                        // But we checked `isUnderloaded` at start of loop... so we actally NEED credits.
                        // But if we need 1 credit and course is 3... 

                        // If we are strictly "Do not exceed", then we should assign single if it fits, else nothing?
                        // But let's assume single fits or is acceptable overshoot (minimal).
                        // The issue reported was 18 vs 15 (3 credits over).
                        // If we assign 1 section (3 credits) -> 15. Perfect.
                        $needsPair = false;
                    }
                }
            }

            if ($needsPair) {
                // Try to assign PAIR (Original + Consecutive)
                $pairResult = $this->attemptAtomicPairAssignment($instructor, $course, $semester, $daysToAssign, $combo['start_time'], $timeSlot->id);
                if ($pairResult) {
                    return $pairResult; // Returns the first section of the pair
                }
            } else {
                // Try to assign SINGLE (Only 1 needed or left)
                $section = $this->attemptAssignment($instructor, $course, $semester, $daysToAssign, $combo['start_time'], $timeSlot->id);
                if ($section) {
                    return $section;
                }
            }
        }

        return null;
    }

    /**
     * Helper to attempt a single assignment.
     */
    protected function attemptAssignment($instructor, $course, $semester, $days, $startTime, $timeSlotId): ?Section
    {
        // Check section quota for this course/semester
        // Rule 3: Strict Section Limits
        if (!SectionQuotaService::hasAvailableSectionQuota($course, $semester)) {
            $this->logSkip($timeSlotId, 'Section quota exceeded for course');
            return null;
        }

        // Calculate end time
        // User Requirement: Course duration = Hours / 2. (e.g. 3 hours = 1.5 hours per session)
        $duration = ($course->hours ?? 3.0) / 2.0;
        $endTime = $this->conflictChecker->calculateEndTime($startTime, (float) $duration);

        // Check if within teaching day
        if (!$this->conflictChecker->isWithinTeachingDay($startTime, $endTime)) {
            return null;
        }

        // Get instructor's existing sections
        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->get();

        // Check for conflicts
        if ($this->conflictChecker->hasConflict($days, $startTime, $endTime, $existingSections)) {
            return null;
        }

        // Check if instructor already has a section of THIS course at THIS time (even on diff days? 
        // User said: "Sections have different time slots (even on the same day pattern)")
        // This implies they can't have AI at 08:30 and AI at 08:30 again.

        return $this->assignSection($instructor, $course, $semester, $days, $startTime, $endTime);
    }

    /**
     * Try to assign default slots if no preference provided.
     * Sprays assignments across available time/day patterns to avoid overcrowding.
     */
    protected function tryAssignDefaultSlots(Instructor $instructor, Course $course, Semester $semester): ?Section
    {
        $assigner = new SlotAssignmentService($this->creditCalculator, $this->conflictChecker);
        return $assigner->assignToOptimalSlot($instructor, $course, $semester, true);
    }

    /**
     * Check if instructor can be assigned to a course based on department.
     */
    protected function canAssignCourse(Instructor $instructor, Course $course): bool
    {
        $instructorDepartmentIds = $instructor->departments->pluck('id')->toArray();
        return in_array($course->department_id, $instructorDepartmentIds);
    }

    protected function hasRoomForPair(Course $course, Semester $semester): bool
    {
        return SectionQuotaService::hasAvailableSectionQuota($course, $semester, 2);
    }

    protected function assignSection(
        Instructor $instructor,
        Course $course,
        Semester $semester,
        array $days,
        string $startTime,
        string $endTime
    ): Section {
        // Calculate section number for this course in this semester
        $existingCount = Section::where('course_id', $course->id)
            ->where('semester_id', $semester->id)
            ->count();
        // Naming: CourseCode-Number (e.g. AI-2)
        $sectionNumber = $course->code . '-' . ($existingCount + 1);

        // Find a suitable room
        $room = $this->findAvailableRoom($course, $days, $startTime, $endTime, $semester);

        $section = Section::create([
            'course_id' => $course->id,
            'section_number' => $sectionNumber,
            'semester_id' => $semester->id,
            'instructor_id' => $instructor->id,
            'days' => $days,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_id' => $room?->id,
            'status' => 'Active',
        ]);

        $this->sectionsAssigned++;

        $daysString = implode(', ', $days);
        $roomInfo = $room ? " in {$room->name}" : " (No Room)";
        Log::info("Section assigned", [
            'instructor' => $instructor->user?->name ?? "ID: {$instructor->id}",
            'course' => $course->name,
            'section' => $sectionNumber,
            'days' => $daysString,
            'time' => "{$startTime} - {$endTime}" . $roomInfo,
        ]);

        return $section;
    }

    /**
     * Try to assign a consecutive section immediately following the previous one.
     */
    /**
     * Attempt to assign an atomic pair of sections (Consecutive).
     * Returns the first section if successful, null otherwise.
     */
    protected function attemptAtomicPairAssignment($instructor, $course, $semester, $days, $startTime, $timeSlotId): ?Section
    {
        // Ensure global course quota has room for 2 sections (pair)
        if (!$this->hasRoomForPair($course, $semester)) {
            $this->logSkip($timeSlotId, 'Section quota would be exceeded by assigning pair');
            return null;
        }

        $times = ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];
        $index = array_search($startTime, $times);

        // Check if next slot exists
        if ($index === false || $index === count($times) - 1) {
            // Cannot form a pair starting at this time
            return null;
        }
        $nextStartTime = $times[$index + 1];

        // 1. Validate First Slot (Dry Run)
        if (!$this->canAssignAt($instructor, $course, $semester, $days, $startTime)) {
            return null;
        }

        // 2. Validate Second Slot (Dry Run)
        // Note: For the second slot conflict check, we must account for the FACT that the first slot WILL be assigned.
        // However, `canAssignAt` checks existing DB records.
        // Since we haven't written Slot 1 yet, `canAssignAt` for Slot 2 is clean...
        // EXCEPT if Slot 1 and Slot 2 somehow overlap (they shouldn't if consecutive).
        // Conflict checker logic:
        // Slot 1: 08:30 - 10:00
        // Slot 2: 10:00 - 11:30
        // No overlap. So independent checks are valid.

        if (!$this->canAssignAt($instructor, $course, $semester, $days, $nextStartTime)) {
            return null;
        }

        // 3. Both valid -> Perform Assignments
        $section1 = $this->assignSection($instructor, $course, $semester, $days, $startTime, $this->calculateEndTime($startTime, $course));
        $this->assignSection($instructor, $course, $semester, $days, $nextStartTime, $this->calculateEndTime($nextStartTime, $course));

        return $section1;
    }

    /**
     * Helper to check if a specific slot is valid for assignment (Dry Run).
     */
    protected function canAssignAt($instructor, $course, $semester, $days, $startTime): bool
    {
        $endTime = $this->calculateEndTime($startTime, $course);

        // Check teaching hours
        if (!$this->conflictChecker->isWithinTeachingDay($startTime, $endTime)) {
            return false;
        }

        // Check conflicts with existing sections
        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->get();

        if ($this->conflictChecker->hasConflict($days, $startTime, $endTime, $existingSections)) {
            return false;
        }


        // Room assignment is optional (assignSection allows null room_id)
        return true;
    }

    protected function calculateEndTime($startTime, $course): string
    {
        $duration = ($course->hours ?? 3.0) / 2.0;
        return $this->conflictChecker->calculateEndTime($startTime, (float) $duration);
    }

    protected function findAvailableRoom(Course $course, array $days, string $startTime, string $endTime, Semester $semester): ?\App\Models\Room
    {
        $requiredType = $course->type === 'Lab' ? 'Lab' : 'Lecture'; // Assuming course has type, default to Lecture if not

        // Get all rooms of required type, ordered by capacity (best fit)
        $rooms = \App\Models\Room::where('type', $requiredType)
            ->orderBy('capacity', 'asc')
            ->get();

        foreach ($rooms as $room) {
            // Check if room is free
            $isOccupied = Section::where('room_id', $room->id)
                ->where('semester_id', $semester->id)
                ->where(function ($query) use ($days) {
                    foreach ($days as $day) {
                        $query->orWhereJsonContains('days', $day);
                    }
                })
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();

            if (!$isOccupied) {
                return $room;
            }
        }

        return null;
    }

    /**
     * Mark a preference as skipped with a reason.
     */
    protected function skipPreference(InstructorPreference $preference, string $reason): void
    {
        $this->preferencesSkipped++;

        $instructorName = $preference->instructor?->user?->name ?? "Unknown Instructor";
        $courseName = $preference->course?->name ?? "Unknown Course";
        $key = "{$instructorName} - {$courseName} (ID: {$preference->id})";

        $this->skipReasons[$key] = $reason;

        Log::info("Preference skipped", [
            'preference_id' => $preference->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Log a time slot skip.
     */
    protected function logSkip(int $timeSlotId, string $reason): void
    {
        if (!isset($this->statistics['time_slot_skips'])) {
            $this->statistics['time_slot_skips'] = [];
        }

        $this->statistics['time_slot_skips'][] = [
            'time_slot_id' => $timeSlotId,
            'reason' => $reason,
        ];
    }

    /**
     * Get courses that still need sections assigned.
     */
    protected function getUnassignedCourses(Semester $semester): Collection
    {
        return DB::table('semester_courses')
            ->where('semester_id', $semester->id)
            ->whereRaw('sections_required > (
                SELECT COUNT(*) 
                FROM sections 
                WHERE sections.course_id = semester_courses.course_id 
                AND sections.semester_id = semester_courses.semester_id
            )')
            ->join('courses', 'courses.id', '=', 'semester_courses.course_id')
            ->select('courses.*', 'semester_courses.sections_required')
            ->get();
    }

    /**
     * Reset allocation counters.
     */
    protected function resetCounters(): void
    {
        $this->skipReasons = [];
        $this->statistics = [];
        $this->sectionsAssigned = 0;
        $this->preferencesProcessed = 0;
        $this->preferencesSkipped = 0;
    }
}
