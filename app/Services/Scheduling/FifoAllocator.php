<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\InstructorPreference;
use App\Models\Section;
use App\Models\Semester;
use App\Services\Scheduling\DTOs\AllocationResult;
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
     * Get all preferences ordered by submission time (FIFO).
     */
    protected function getPreferencesInFifoOrder(Semester $semester): Collection
    {
        return InstructorPreference::query()
            ->where('semester_id', $semester->id)
            ->with(['instructor.departments', 'course', 'timeSlots'])
            ->orderBy('submission_time', 'asc')
            ->get();
    }

    /**
     * Process a single instructor preference.
     */
    protected function processPreference(InstructorPreference $preference, Semester $semester): void
    {
        $this->preferencesProcessed++;

        $instructor = $preference->instructor;
        $course = $preference->course;

        // Requirement: Once an instructor reaches their minimum required credit hours, stop assigning them sections.
        if (!$this->creditCalculator->isUnderloaded($instructor, $semester)) {
            $this->skipPreference($preference, "Stopping Condition: Instructor already reached min credits.");
            return;
        }

        // Rule: Department Qualification Check
        if (!$this->canAssignCourse($instructor, $course)) {
            $this->skipPreference($preference, 'Department qualification rule: Instructor not in course department');
            return;
        }

        // Rule: Section Limit (Max 2 sections of the same course)
        $currentAssignmentCount = $this->getCourseAssignmentCount($instructor, $course, $semester);
        if ($currentAssignmentCount >= 2) {
            $this->skipPreference($preference, 'Section limit reached: Max 2 sections per course.');
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
        foreach ($timeSlots as $timeSlot) {
            // Check if we've already assigned 2 sections of THIS course
            if ($this->getCourseAssignmentCount($instructor, $course, $semester) >= 2) {
                break;
            }

            // Check if instructor reached min credits inside the loop
            if (!$this->creditCalculator->isUnderloaded($instructor, $semester)) {
                break;
            }

            // Try to assign this time slot
            if ($this->tryAssignTimeSlot($instructor, $course, $timeSlot, $semester)) {
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
    ): bool {
        // days is now more likely to be an array from my controller update
        $daysValue = $timeSlot->days;
        if (is_string($daysValue)) {
            $decoded = json_decode($daysValue, true);
            $daysValue = $decoded ?: [$daysValue];
        }

        // If days are NULL/Empty, user allows ANY day -> Try all patterns
        $dayPatterns = (is_array($daysValue) && !empty($daysValue))
            ? $daysValue
            : ['Sun/Wed', 'Mon/Thu', 'Tue/Sat'];

        // Map preferred time ranges to specific slots
        // Morning: 08:30–11:30
        // Noon: 11:30–14:30
        // Afternoon: 14:30–17:30

        $baseStartTime = $timeSlot->start_time;
        $slotsToTry = [];

        if ($baseStartTime === '08:30:00') {
            $slotsToTry = ['08:30:00', '10:00:00'];
        } elseif ($baseStartTime === '11:30:00' || $baseStartTime === '12:00:00') {
            $slotsToTry = ['11:30:00', '13:00:00'];
        } elseif ($baseStartTime === '14:30:00' || $baseStartTime === '14:00:00') {
            $slotsToTry = ['14:30:00', '16:00:00'];
        } elseif (!empty($baseStartTime)) {
            $slotsToTry = [$baseStartTime];
        } else {
            // If time is NULL, user allows ANY time -> Try all standard slots
            $slotsToTry = ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];
        }

        foreach ($dayPatterns as $pattern) {
            $daysToAssign = $this->conflictChecker->getDayPair($pattern);

            // Randomize slots order to avoid clustering? Or keep strict order?
            // "Strict" usually implies trying what is asked. If "Any", random is good.
            if ($baseStartTime === null) {
                shuffle($slotsToTry);
            }

            foreach ($slotsToTry as $startTime) {
                if ($this->attemptAssignment($instructor, $course, $semester, $daysToAssign, $startTime, $timeSlot->id)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Helper to attempt a single assignment.
     */
    protected function attemptAssignment($instructor, $course, $semester, $days, $startTime, $timeSlotId): bool
    {
        // Check section quota for this course/semester
        // User Request: Make sections if there are no section available (Auto-Scaling)
        // We relax the quota check to allow creating new sections if needed.
        // if (!$this->hasAvailableSectionQuota($course, $semester)) {
        //    $this->logSkip($timeSlotId, 'Section quota exceeded for course');
        //    return false;
        // }

        // Calculate end time
        // User Requirement: Course duration = Hours / 2. (e.g. 3 hours = 1.5 hours per session)
        $duration = ($course->hours ?? 3.0) / 2.0;
        $endTime = $this->conflictChecker->calculateEndTime($startTime, (float) $duration);

        // Check if within teaching day
        if (!$this->conflictChecker->isWithinTeachingDay($startTime, $endTime)) {
            return false;
        }

        // Get instructor's existing sections
        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->get();

        // Check for conflicts
        if ($this->conflictChecker->hasConflict($days, $startTime, $endTime, $existingSections)) {
            return false;
        }

        // Check if instructor already has a section of THIS course at THIS time (even on diff days? 
        // User said: "Sections have different time slots (even on the same day pattern)")
        // This implies they can't have AI at 08:30 and AI at 08:30 again.

        $this->assignSection($instructor, $course, $semester, $days, $startTime, $endTime);
        return true;
    }

    /**
     * Try to assign default slots if no preference provided.
     * Sprays assignments across available time/day patterns to avoid overcrowding.
     */
    protected function tryAssignDefaultSlots(Instructor $instructor, Course $course, Semester $semester): bool
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

    /**
     * Get the number of sections already assigned to instructor for a course.
     */
    protected function getCourseAssignmentCount(
        Instructor $instructor,
        Course $course,
        Semester $semester
    ): int {
        return Section::query()
            ->where('semester_id', $semester->id)
            ->where('instructor_id', $instructor->id)
            ->where('course_id', $course->id)
            ->count();
    }

    /**
     * Check if course has available section quota.
     */
    protected function hasAvailableSectionQuota(Course $course, Semester $semester): bool
    {
        // Get the required sections from semester_courses pivot
        $semesterCourse = DB::table('semester_courses')
            ->where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$semesterCourse) {
            return false;
        }

        $sectionsRequired = $semesterCourse->sections_required ?? 0;

        // Count currently assigned sections
        $assignedCount = Section::query()
            ->where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->count();

        return $assignedCount < $sectionsRequired;
    }

    /**
     * Assign a section to an instructor.
     */
    protected function assignSection(
        Instructor $instructor,
        Course $course,
        Semester $semester,
        array $days,
        string $startTime,
        string $endTime
    ): void {
        // Calculate section number for this course in this semester
        $existingCount = Section::where('course_id', $course->id)
            ->where('semester_id', $semester->id)
            ->count();
        // Naming: CourseCode-Number (e.g. AI-2)
        $sectionNumber = $course->code . '-' . ($existingCount + 1);

        // Find a suitable room
        $room = $this->findAvailableRoom($course, $days, $startTime, $endTime, $semester);

        Section::create([
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

        $key = ($preference->instructor->user?->name ?? "ID: {$preference->instructor->id}") . " - {$preference->course->name}";
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
