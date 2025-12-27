<?php

namespace App\Services\Scheduling;

use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use App\Services\Scheduling\DTOs\SchedulingResult;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulerService
{
    public function __construct(
        protected FifoAllocator $fifoAllocator,
        protected CreditHourCalculator $creditCalculator,
        protected TimeConflictChecker $conflictChecker
    ) {
    }

    /**
     * Generate schedule for a semester using FIFO allocation.
     *
     * @param Semester $semester
     * @param array $options {
     *     @type bool $enable_least_chosen Run second pass for least-chosen courses
     *     @type bool $clear_existing Clear existing schedule before generation
     *     @type bool $notify_admin Send admin notifications
     *     @type bool $strict_mode Fail on any constraint violation
     * }
     * @return SchedulingResult
     */
    public function generateSchedule(Semester $semester, array $options = []): SchedulingResult
    {
        // Default options
        $options = array_merge([
            'enable_least_chosen' => true,
            'clear_existing' => false,
            'notify_admin' => true,
            'strict_mode' => false,
        ], $options);

        DB::beginTransaction();

        try {
            Log::info("Starting schedule generation for semester {$semester->id}", $options);

            // Clear existing schedule if requested
            if ($options['clear_existing']) {
                $this->clearSchedule($semester);
            }

            // Run FIFO allocation
            $allocationResult = $this->fifoAllocator->allocate($semester);

            // Optional: Assign least-chosen courses
            if ($options['enable_least_chosen'] && $allocationResult->hasUnassignedCourses()) {
                $this->assignLeastChosenCourses($semester);
            }

            // Calculate credit hours and identify underloaded instructors
            $underloadedInstructors = $this->creditCalculator->getUnderloadedInstructors($semester);

            // Validate the schedule
            $isValid = $this->validateSchedule($semester, $options['strict_mode']);

            // Generate admin notifications
            $notifications = $this->generateNotifications($allocationResult, $underloadedInstructors);

            DB::commit();

            $result = new SchedulingResult(
                allocationResult: $allocationResult,
                underloadedInstructors: $underloadedInstructors,
                isValid: $isValid,
                adminNotifications: $notifications
            );

            Log::info("Schedule generation completed", [
                'sections_assigned' => $allocationResult->totalSectionsAssigned,
                'underloaded_count' => $underloadedInstructors->count(),
                'is_valid' => $isValid,
            ]);

            return $result;

        } catch (Exception $e) {
            DB::rollBack();

            Log::error("Schedule generation failed: {$e->getMessage()}", [
                'semester_id' => $semester->id,
                'exception' => $e,
            ]);

            return new SchedulingResult(
                allocationResult: new \App\Services\Scheduling\DTOs\AllocationResult(
                    totalSectionsAssigned: 0,
                    totalPreferencesProcessed: 0,
                    totalPreferencesSkipped: 0,
                    unassignedCourses: collect()
                ),
                underloadedInstructors: collect(),
                isValid: false,
                adminNotifications: [],
                errorMessage: $e->getMessage()
            );
        }
    }

    /**
     * Assign least-chosen courses to eligible instructors.
     * This is a second-pass algorithm for courses with low preference counts.
     */
    public function assignLeastChosenCourses(Semester $semester): void
    {
        Log::info("Starting least-chosen course assignment for semester {$semester->id}");

        // Get courses that still need sections
        $unassignedCourses = $this->getUnassignedCourses($semester);

        // Sort by preference count (ascending) - least chosen first
        $coursesWithCounts = $unassignedCourses->map(function ($course) use ($semester) {
            $preferenceCount = \App\Models\InstructorPreference::query()
                ->where('semester_id', $semester->id)
                ->where('course_id', $course->id)
                ->count();

            return [
                'course' => $course,
                'preference_count' => $preferenceCount,
                'sections_needed' => $this->getSectionsNeeded($course, $semester),
            ];
        })->sortBy('preference_count');

        foreach ($coursesWithCounts as $courseData) {
            $course = $courseData['course'];
            $sectionsNeeded = $courseData['sections_needed'];

            // Find eligible instructors (same department, not overloaded, no conflicts)
            $eligibleInstructors = $this->findEligibleInstructors($course, $semester);

            foreach ($eligibleInstructors as $instructor) {
                if ($sectionsNeeded <= 0) {
                    break;
                }

                // Try to assign a section during available time slots
                if ($this->tryAssignAvailableSlot($instructor, $course, $semester)) {
                    $sectionsNeeded--;
                }
            }
        }
    }

    /**
     * Clear all scheduled sections for a semester.
     */
    public function clearSchedule(Semester $semester): void
    {
        $deletedCount = Section::query()
            ->where('semester_id', $semester->id)
            ->delete();

        Log::info("Cleared {$deletedCount} sections for semester {$semester->id}");
    }

    /**
     * Get a comprehensive report of the schedule.
     */
    public function getScheduleReport(Semester $semester): array
    {
        $sections = Section::query()
            ->where('semester_id', $semester->id)
            ->with(['instructor', 'course'])
            ->get();

        $instructorLoads = $sections->groupBy('instructor_id')->map(function ($instructorSections) use ($semester) {
            $instructor = $instructorSections->first()->instructor;
            return $this->creditCalculator->getLoadStatus($instructor, $semester);
        });

        return [
            'semester' => $semester,
            'total_sections' => $sections->count(),
            'total_instructors' => $sections->pluck('instructor_id')->unique()->count(),
            'sections' => $sections,
            'instructor_loads' => $instructorLoads,
            'underloaded' => $instructorLoads->filter(fn($load) => $load->isUnderloaded()),
            'conflicts' => $this->detectAllConflicts($semester),
        ];
    }

    /**
     * Manually override a section assignment.
     */
    public function overrideAssignment(Section $section, Instructor $newInstructor): bool
    {
        // Validate the override doesn't create conflicts
        $endTime = $this->conflictChecker->calculateEndTime(
            $section->start_time,
            (float) ($section->course->hours ?? 3.0)
        );

        $existingSections = $newInstructor->sections()
            ->where('semester_id', $section->semester_id)
            ->where('id', '!=', $section->id)
            ->get();

        $days = is_array($section->days) ? $section->days : [$section->days];

        foreach ($days as $day) {
            if ($this->conflictChecker->hasConflict($day, $section->start_time, $endTime, $existingSections)) {
                Log::warning("Override rejected: Time conflict detected", [
                    'section_id' => $section->id,
                    'new_instructor_id' => $newInstructor->id,
                ]);
                return false;
            }
        }

        // Perform the override
        $section->update(['instructor_id' => $newInstructor->id]);

        Log::info("Section assignment overridden", [
            'section_id' => $section->id,
            'new_instructor_id' => $newInstructor->id,
        ]);

        return true;
    }

    /**
     * Validate the schedule for conflicts and constraint violations.
     */
    protected function validateSchedule(Semester $semester, bool $strict = false): bool
    {
        $sections = Section::query()
            ->where('semester_id', $semester->id)
            ->with(['instructor', 'course'])
            ->get();

        // Check for time conflicts
        $conflicts = $this->detectAllConflicts($semester);
        if ($conflicts->isNotEmpty()) {
            Log::warning("Schedule validation: Time conflicts detected", [
                'conflict_count' => $conflicts->count(),
            ]);

            if ($strict) {
                return false;
            }
        }

        // Check for section limit violations
        $violations = $this->checkSectionLimitViolations($semester);
        if ($violations->isNotEmpty()) {
            Log::warning("Schedule validation: Section limit violations", [
                'violation_count' => $violations->count(),
            ]);

            if ($strict) {
                return false;
            }
        }

        return true;
    }

    /**
     * Detect all time conflicts in the schedule.
     */
    protected function detectAllConflicts(Semester $semester): Collection
    {
        $conflicts = collect();
        $instructors = \App\Models\Instructor::query()
            ->whereHas('sections', fn($q) => $q->where('semester_id', $semester->id))
            ->with(['sections' => fn($q) => $q->where('semester_id', $semester->id)])
            ->get();

        foreach ($instructors as $instructor) {
            $sections = $instructor->sections;

            // Check each day
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days as $day) {
                $dayConflicts = $this->conflictChecker->getConflicts($sections, $day);
                if ($dayConflicts->isNotEmpty()) {
                    $conflicts = $conflicts->merge($dayConflicts);
                }
            }
        }

        return $conflicts;
    }

    /**
     * Check for section limit violations (more than 2 sections per course).
     */
    protected function checkSectionLimitViolations(Semester $semester): Collection
    {
        return DB::table('sections')
            ->select('instructor_id', 'course_id', DB::raw('COUNT(*) as section_count'))
            ->where('semester_id', $semester->id)
            ->groupBy('instructor_id', 'course_id')
            ->having('section_count', '>', 2)
            ->get();
    }

    /**
     * Generate notifications for admin.
     */
    protected function generateNotifications($allocationResult, Collection $underloadedInstructors): array
    {
        $notifications = [];

        if ($underloadedInstructors->isNotEmpty()) {
            $notifications[] = [
                'type' => 'warning',
                'title' => 'Underloaded Instructors',
                'message' => sprintf(
                    '%d instructor(s) are below minimum credit hour requirements',
                    $underloadedInstructors->count()
                ),
                'data' => $underloadedInstructors->map(fn($load) => $load->toArray())->toArray(),
            ];
        }

        if ($allocationResult->hasUnassignedCourses()) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Unassigned Courses',
                'message' => sprintf(
                    '%d course(s) still need section assignments',
                    $allocationResult->unassignedCourses->count()
                ),
                'data' => $allocationResult->unassignedCourses->toArray(),
            ];
        }

        if ($allocationResult->totalPreferencesSkipped > 0) {
            $notifications[] = [
                'type' => 'info',
                'title' => 'Skipped Preferences',
                'message' => sprintf(
                    '%d preference(s) were skipped due to conflicts or constraints',
                    $allocationResult->totalPreferencesSkipped
                ),
            ];
        }

        return $notifications;
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
            ->get()
            ->map(fn($row) => \App\Models\Course::find($row->id));
    }

    /**
     * Get number of sections still needed for a course.
     */
    protected function getSectionsNeeded($course, Semester $semester): int
    {
        $semesterCourse = DB::table('semester_courses')
            ->where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$semesterCourse) {
            return 0;
        }

        $assignedCount = Section::query()
            ->where('semester_id', $semester->id)
            ->where('course_id', $course->id)
            ->count();

        return max(0, $semesterCourse->sections_required - $assignedCount);
    }

    /**
     * Find instructors eligible to teach a course.
     */
    protected function findEligibleInstructors($course, Semester $semester): Collection
    {
        // Get instructors in the same department
        $eligibleInstructors = \App\Models\Instructor::query()
            ->whereHas('departments', fn($q) => $q->where('departments.id', $course->department_id))
            ->with(['sections' => fn($q) => $q->where('semester_id', $semester->id)])
            ->get();

        // Rule 1: Filter out instructors who already have 2 sections of this course
        // Rule 2: Only allow a 2nd section if they are currently underloaded
        return $eligibleInstructors->filter(function ($instructor) use ($course, $semester) {
            $count = Section::query()
                ->where('semester_id', $semester->id)
                ->where('instructor_id', $instructor->id)
                ->where('course_id', $course->id)
                ->count();

            if ($count >= 2) {
                return false;
            }

            if ($count == 1) {
                // Allow a 2nd section ONLY if they are still underloaded
                return $this->creditCalculator->isUnderloaded($instructor, $semester);
            }

            return true;
        })->sortByDesc(function ($instructor) use ($semester) {
            // Prioritize those who are furthest from their minimum requirement
            $current = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
            $min = $this->creditCalculator->getMinimumCredits($instructor, $semester);
            return max(0, $min - $current);
        });
    }

    /**
     * Try to assign a section during an available time slot.
     */
    protected function tryAssignAvailableSlot(Instructor $instructor, $course, Semester $semester): bool
    {
        // UNIVERSITY RULE: Standard time slots are based on day pairs
        $standardSlots = [
            ['day' => 'Sunday', 'start' => '08:30:00'],
            ['day' => 'Sunday', 'start' => '10:00:00'],
            ['day' => 'Sunday', 'start' => '13:00:00'],
            ['day' => 'Monday', 'start' => '08:30:00'],
            ['day' => 'Monday', 'start' => '10:00:00'],
            ['day' => 'Monday', 'start' => '13:00:00'],
            ['day' => 'Tuesday', 'start' => '08:30:00'],
            ['day' => 'Tuesday', 'start' => '10:00:00'],
            ['day' => 'Tuesday', 'start' => '13:00:00'],
        ];

        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->get();

        // Check C.H. Capacity (Hard limit 18.0)
        $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
        $maxCredits = $this->creditCalculator->getMaxCredits($instructor); // 18.0
        $courseCredits = (float) ($course->credits ?? 3.0);

        if ($currentCredits + $courseCredits > $maxCredits) {
            Log::info("Least-chosen skipping instructor: Capacity reached", [
                'instructor' => $instructor->name,
                'current' => $currentCredits,
                'max' => $maxCredits
            ]);
            return false;
        }

        foreach ($standardSlots as $slot) {
            $daysToAssign = $this->conflictChecker->getDayPair($slot['day']);
            $endTime = $this->conflictChecker->calculateEndTime($slot['start'], (float) ($course->hours ?? 3.0));

            // Check if within teaching day
            if (!$this->conflictChecker->isWithinTeachingDay($slot['start'], $endTime)) {
                continue;
            }

            // Check for conflicts on BOTH days of the pair
            if ($this->conflictChecker->hasConflict($daysToAssign, $slot['start'], $endTime, $existingSections)) {
                continue;
            }

            // Assign the section (pair)
            Section::create([
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'instructor_id' => $instructor->id,
                'days' => $daysToAssign,
                'start_time' => $slot['start'],
                'end_time' => $endTime,
                'status' => 'Active',
            ]);

            $daysString = implode(', ', $daysToAssign);
            Log::info("Least-chosen course assigned", [
                'instructor' => $instructor->name,
                'course' => $course->name,
                'days' => $daysString,
                'time' => "{$slot['start']} - {$endTime}",
            ]);

            return true;
        }

        return false;
    }
}
