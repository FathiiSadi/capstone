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

        // Rule 1: Department Qualification Check
        if (!$this->canAssignCourse($instructor, $course)) {
            $this->skipPreference($preference, 'Department qualification rule: Instructor not in course department');
            return;
        }

        // Rule 2: Section Limit & Underload-Filling Check
        $currentAssignmentCount = $this->getCourseAssignmentCount($instructor, $course, $semester);
        $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
        $minimumCredits = $this->creditCalculator->getMinimumCredits($instructor, $semester);
        $maxCredits = $this->creditCalculator->getMaxCredits($instructor);
        $courseCredits = (float) ($course->credits ?? 3.0);

        if ($currentAssignmentCount >= 1) {
            // If they already have 1 section, only allow a 2nd if they are STILL underloaded
            if ($currentCredits >= $minimumCredits) {
                $this->skipPreference($preference, "Section limit: Instructor already meets minimum load ({$currentCredits} >= {$minimumCredits}) and has 1 section of this course.");
                return;
            }

            if ($currentAssignmentCount >= 2) {
                $this->skipPreference($preference, 'Section limit reached: Max 2 sections per course.');
                return;
            }
        }

        // Rule 3: Hard Credit Capacity Check
        if ($currentCredits + $courseCredits > $maxCredits) {
            $this->skipPreference($preference, "C.H. limit reached: Allocation would exceed hard cap of {$maxCredits}.");
            return;
        }

        // Process each time slot preference
        $timeSlots = $preference->timeSlots;

        if ($timeSlots->isEmpty()) {
            $this->skipPreference($preference, 'No time slots specified');
            return;
        }

        $assigned = false;
        foreach ($timeSlots as $timeSlot) {
            // Check if we've already assigned 2 sections
            if ($this->getCourseAssignmentCount($instructor, $course, $semester) >= 2) {
                break;
            }

            // Try to assign this time slot
            if ($this->tryAssignTimeSlot($instructor, $course, $timeSlot, $semester)) {
                $assigned = true;
                // Don't break - allow up to 2 sections per preference
            }
        }

        if (!$assigned) {
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
        // Get the days from the time slot
        $daysValue = $timeSlot->days;

        // If it's a JSON string (due to older data or mismatch), decode it
        if (is_string($daysValue) && (str_starts_with($daysValue, '[') || str_starts_with($daysValue, '{'))) {
            $daysValue = json_decode($daysValue, true);
        }

        $daysRequested = is_array($daysValue) ? $daysValue : [$daysValue];

        // Process each day in the preference
        foreach ($daysRequested as $requestedDay) {
            // UNIVERSITY RULE: Every section is assigned as a PAIR
            // Sunday-Wednesday, Monday-Thursday, Tuesday-Saturday
            $daysToAssign = $this->conflictChecker->getDayPair($requestedDay);

            // Check section quota for this course/semester
            if (!$this->hasAvailableSectionQuota($course, $semester)) {
                $this->logSkip($timeSlot->id, 'Section quota exceeded for course');
                continue;
            }

            // Calculate end time based on course hours
            $startTime = $timeSlot->start_time ?? '08:30:00';
            $endTime = $this->conflictChecker->calculateEndTime($startTime, (float) ($course->hours ?? 3.0));

            // Check if within teaching day
            if (!$this->conflictChecker->isWithinTeachingDay($startTime, $endTime)) {
                $this->logSkip($timeSlot->id, 'Time slot outside teaching hours');
                continue;
            }

            // Get instructor's existing sections
            $existingSections = $instructor->sections()
                ->where('semester_id', $semester->id)
                ->get();

            // Rule 3: Time Conflict Check on BOTH days of the pair
            if ($this->conflictChecker->hasConflict($daysToAssign, $startTime, $endTime, $existingSections)) {
                $daysString = implode(' & ', $daysToAssign);
                $this->logSkip($timeSlot->id, "Time conflict on {$daysString} at {$startTime}");
                continue;
            }

            // All checks passed - assign the section (pair)
            $this->assignSection($instructor, $course, $semester, $daysToAssign, $startTime, $endTime);
            return true;
        }

        return false;
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
        Section::create([
            'course_id' => $course->id,
            'semester_id' => $semester->id,
            'instructor_id' => $instructor->id,
            'days' => $days,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'Active',
        ]);

        $this->sectionsAssigned++;

        $daysString = implode(', ', $days);
        Log::info("Section assigned", [
            'instructor' => $instructor->name,
            'course' => $course->name,
            'days' => $daysString,
            'time' => "{$startTime} - {$endTime}",
        ]);
    }

    /**
     * Mark a preference as skipped with a reason.
     */
    protected function skipPreference(InstructorPreference $preference, string $reason): void
    {
        $this->preferencesSkipped++;

        $key = "{$preference->instructor->name} - {$preference->course->name}";
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
