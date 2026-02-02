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

            // Multi-pass FIFO allocation: run until no new sections are assigned (full schedule in one go)
            $maxPasses = (int) ($options['max_allocation_passes'] ?? 15);
            $allocationResult = $this->runAllocationPasses($semester, $maxPasses);

            // Optional: Assign least-chosen courses (multi-pass until no progress, to meet minimum load)
            if ($options['enable_least_chosen'] && $allocationResult->hasUnassignedCourses()) {
                $this->runLeastChosenPasses($semester, 10);
            }

            // Calculate credit hours and identify underloaded instructors
            $underloadedInstructors = $this->creditCalculator->getUnderloadedInstructors($semester);

            // Validate the schedule
            $isValid = $this->validateSchedule($semester, $options['strict_mode']);

            DB::commit();

            // Refresh allocation result to show updated counts from BOTH passes
            $finalAssignedCount = Section::where('semester_id', $semester->id)->count();
            $finalUnassignedCourses = $this->getUnassignedCourses($semester);

            $allocationResult = new \App\Services\Scheduling\DTOs\AllocationResult(
                totalSectionsAssigned: $finalAssignedCount,
                totalPreferencesProcessed: $allocationResult->totalPreferencesProcessed,
                totalPreferencesSkipped: $allocationResult->totalPreferencesSkipped,
                unassignedCourses: $finalUnassignedCourses,
                skipReasons: $allocationResult->skipReasons,
                statistics: $allocationResult->statistics
            );

            // Generate admin notifications based on FINAL results
            $notifications = $this->generateNotifications($allocationResult, $underloadedInstructors);

            $result = new SchedulingResult(
                allocationResult: $allocationResult,
                underloadedInstructors: $underloadedInstructors,
                isValid: $isValid,
                adminNotifications: $notifications
            );

            Log::info("Schedule generation completed", [
                'sections_assigned' => $finalAssignedCount,
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
     * Run FIFO allocation in multiple passes until no new sections are assigned.
     * Produces the full schedule in one invocation (same as clicking Generate multiple times without clearing).
     */
    protected function runAllocationPasses(Semester $semester, int $maxPasses): \App\Services\Scheduling\DTOs\AllocationResult
    {
        $totalAssigned = 0;
        $totalProcessed = 0;
        $totalSkipped = 0;
        $skipReasons = [];
        $statistics = [];
        $pass = 0;

        do {
            $pass++;
            $beforeCount = Section::where('semester_id', $semester->id)->count();
            $result = $this->fifoAllocator->allocate($semester);
            $afterCount = Section::where('semester_id', $semester->id)->count();
            $assignedThisPass = $afterCount - $beforeCount;

            $totalAssigned += $result->totalSectionsAssigned;
            $totalProcessed += $result->totalPreferencesProcessed;
            $totalSkipped += $result->totalPreferencesSkipped;
            $skipReasons = array_merge($skipReasons, $result->skipReasons);
            $statistics = array_merge($statistics, $result->statistics ?? []);

            Log::info("Allocation pass {$pass}: assigned {$assignedThisPass} sections", [
                'pass' => $pass,
                'assigned_this_pass' => $assignedThisPass,
                'total_sections' => $afterCount,
            ]);
        } while ($assignedThisPass > 0 && $pass < $maxPasses);

        $unassignedCourses = $this->getUnassignedCourses($semester);

        return new \App\Services\Scheduling\DTOs\AllocationResult(
            totalSectionsAssigned: $totalAssigned,
            totalPreferencesProcessed: $totalProcessed,
            totalPreferencesSkipped: $totalSkipped,
            unassignedCourses: $unassignedCourses,
            skipReasons: $skipReasons,
            statistics: $statistics
        );
    }

    /**
     * Run least-chosen assignment in multiple passes until no new sections are assigned.
     * Helps meet minimum load for instructors.
     */
    protected function runLeastChosenPasses(Semester $semester, int $maxPasses): void
    {
        for ($pass = 1; $pass <= $maxPasses; $pass++) {
            $beforeCount = Section::where('semester_id', $semester->id)->count();
            $this->assignLeastChosenCourses($semester);
            $afterCount = Section::where('semester_id', $semester->id)->count();
            if (($afterCount - $beforeCount) === 0) {
                break;
            }
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
        $duration = ($section->course->hours ?? 3.0) / 2.0;
        $endTime = $this->conflictChecker->calculateEndTime(
            $section->start_time,
            (float) $duration
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

        // Check C.H. Capacity / Load Limit
        if (!$this->creditCalculator->isUnderloaded($newInstructor, $section->semester)) {
            Log::warning("Override rejected: Load limit reached", [
                'section_id' => $section->id,
                'new_instructor_id' => $newInstructor->id,
            ]);
            return false;
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

        // Rule 1: Allow instructors who are NOT OVERLOADED (Under Max Credits)
        return $eligibleInstructors->filter(function ($instructor) use ($course, $semester) {
            $current = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
            $max = $this->creditCalculator->getMaxCredits($instructor);
            $courseCredits = (float) ($course->credits ?? 3.0);

            if ($current + $courseCredits > $max) {
                return false;
            }

            $count = Section::query()
                ->where('semester_id', $semester->id)
                ->where('instructor_id', $instructor->id)
                ->where('course_id', $course->id)
                ->count();

            // Rule 2: Max 2 sections per course
            if ($count >= 2) {
                return false;
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
        $patterns = ['Sunday', 'Monday', 'Tuesday'];
        $times = ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];

        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->get();

        // Check C.H. Capacity (Hard limit 18.0)
        $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
        $maxCredits = $this->creditCalculator->getMaxCredits($instructor); // 18.0
        $courseCredits = (float) ($course->credits ?? 3.0);

        if ($currentCredits + $courseCredits > $maxCredits) {
            Log::info("Least-chosen skipping instructor: Capacity reached", [
                'instructor' => $instructor->user->name ?? "ID: {$instructor->id}",
                'current' => $currentCredits,
                'max' => $maxCredits
            ]);
            return false;
        }

        // Re-check stopping condition before each assignment
        if (!$this->creditCalculator->isUnderloaded($instructor, $semester)) {
            return false;
        }

        $potentialSlots = [];
        foreach ($patterns as $pattern) {
            foreach ($times as $startTime) {
                // Count current global occupancy for this slot to spread the load
                $occupancy = Section::where('semester_id', $semester->id)
                    ->where('start_time', $startTime)
                    ->where(function ($query) use ($pattern) {
                        $days = $this->conflictChecker->getDayPair($pattern);
                        foreach ($days as $day) {
                            $query->orWhereJsonContains('days', $day);
                        }
                    })
                    ->count();

                $potentialSlots[] = [
                    'pattern' => $pattern,
                    'start_time' => $startTime,
                    'occupancy' => $occupancy
                ];
            }
        }

        // Sort by occupancy ascending (least busy slots first), then deterministically by pattern and time
        usort($potentialSlots, function ($a, $b) {
            if ($a['occupancy'] !== $b['occupancy']) {
                return $a['occupancy'] <=> $b['occupancy'];
            }
            $keyA = $a['pattern'] . '|' . $a['start_time'];
            $keyB = $b['pattern'] . '|' . $b['start_time'];
            return strcmp($keyA, $keyB);
        });

        foreach ($potentialSlots as $slot) {
            $daysToAssign = $this->conflictChecker->getDayPair($slot['pattern']);
            $duration = ($course->hours ?? 3.0) / 2.0;
            $endTime = $this->conflictChecker->calculateEndTime($slot['start_time'], (float) $duration);

            // Check if within teaching day
            if (!$this->conflictChecker->isWithinTeachingDay($slot['start_time'], $endTime)) {
                continue;
            }

            // Check for conflicts on BOTH days of the pair
            if ($this->conflictChecker->hasConflict($daysToAssign, $slot['start_time'], $endTime, $existingSections)) {
                continue;
            }

            // Calculate section number for this course in this semester
            $existingCount = Section::where('course_id', $course->id)
                ->where('semester_id', $semester->id)
                ->count();
            $sectionNumber = "S" . ($existingCount + 1);

            // Assign the section (pair)
            Section::create([
                'course_id' => $course->id,
                'section_number' => $sectionNumber,
                'semester_id' => $semester->id,
                'instructor_id' => $instructor->id,
                'days' => $daysToAssign,
                'start_time' => $slot['start_time'],
                'end_time' => $endTime,
                'status' => 'Active',
            ]);

            $daysString = implode(', ', $daysToAssign);
            Log::info("Least-chosen course assigned", [
                'instructor' => $instructor->user->name ?? "ID: {$instructor->id}",
                'course' => $course->name,
                'section' => $sectionNumber,
                'days' => $daysString,
                'time' => "{$slot['start_time']} - {$endTime}",
            ]);

            return true;
        }

        return false;
    }
}
