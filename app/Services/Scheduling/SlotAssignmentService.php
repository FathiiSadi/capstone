<?php

namespace App\Services\Scheduling;

use App\Models\Course;
use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use Illuminate\Support\Facades\Log;

class SlotAssignmentService
{
    public function __construct(
        protected CreditHourCalculator $creditCalculator,
        protected TimeConflictChecker $conflictChecker
    ) {
    }

    /**
     * Try to assign a section to an available slot, prioritizing the least occupied ones.
     * This logic is extracted to be reusable by both the SchedulerService and Import process.
     * 
     * @param Instructor $instructor
     * @param Course $course
     * @param Semester $semester
     * @param bool $checkLoad If true, adheres to instructor load limits (false for forced imports)
     * @return bool True if assigned
     */
    /**
     * Finds the best available slot based on global occupancy and conflicts.
     * Returns array with 'days', 'start_time', 'end_time' or null if none found.
     */
    public function findOptimalSlot(Instructor $instructor, Course $course, Semester $semester, ?int $ignoreSectionId = null): ?array
    {
        // UNIVERSITY RULE: Standard time slots are based on day pairs
        $patterns = ['Sun/Wed', 'Mon/Thu', 'Tue/Sat'];
        // Expanded time slots to cover full day
        $times = ['08:30:00', '10:00:00', '11:30:00', '13:00:00', '14:30:00', '16:00:00'];

        $existingSections = $instructor->sections()
            ->where('semester_id', $semester->id)
            ->when($ignoreSectionId, fn($q) => $q->where('id', '!=', $ignoreSectionId))
            ->get();

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

                // Rule 4: Minimize Same-Course Conflicts
                // Check if THIS course is already scheduled at this time (on overlapping days)
                $sameCourseConflict = Section::where('semester_id', $semester->id)
                    ->where('course_id', $course->id)
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
                    'occupancy' => $occupancy,
                    'same_course_conflict' => $sameCourseConflict,
                ];
            }
        }

        // Sort by same-course conflict (ASC) first, then occupancy (ASC)
        usort($potentialSlots, function ($a, $b) {
            // Priority 1: Avoid same course at same time
            if ($a['same_course_conflict'] !== $b['same_course_conflict']) {
                return $a['same_course_conflict'] <=> $b['same_course_conflict'];
            }
            // Priority 2: Standard load balancing
            if ($a['occupancy'] === $b['occupancy']) {
                return rand(-1, 1);
            }
            return $a['occupancy'] <=> $b['occupancy'];
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

            return [
                'days' => $daysToAssign,
                'start_time' => $slot['start_time'],
                'end_time' => $endTime
            ];
        }

        return null;
    }

    public function assignToOptimalSlot(Instructor $instructor, Course $course, Semester $semester, bool $checkLoad = true): ?Section
    {
        if (!SectionQuotaService::hasAvailableSectionQuota($course, $semester)) {
            return null;
        }

        // 1. Capacity Check
        if ($checkLoad) {
            if (!$this->creditCalculator->isUnderloaded($instructor, $semester)) {
                return null;
            }

            $currentCredits = $this->creditCalculator->calculateTotalCredits($instructor, $semester);
            $maxCredits = $this->creditCalculator->getMaxCredits($instructor);
            $courseCredits = (float) ($course->credits ?? 3.0);

            if ($currentCredits + $courseCredits > $maxCredits) {
                return null;
            }
        }

        // 2. Find optimal slot
        $slot = $this->findOptimalSlot($instructor, $course, $semester);

        if ($slot) {
            $perInstructorLimit = SectionQuotaService::getPerInstructorSectionLimit($course, $semester);
            if (SectionQuotaService::getInstructorCourseCount($instructor, $course, $semester) >= $perInstructorLimit) {
                return null;
            }

            return $this->createSection($instructor, $course, $semester, $slot['days'], $slot['start_time'], $slot['end_time']);
        }

        return null;
    }

    protected function createSection(Instructor $instructor, Course $course, Semester $semester, array $days, string $startTime, string $endTime): Section
    {
        // Calculate section number
        $existingCount = Section::where('course_id', $course->id)
            ->where('semester_id', $semester->id)
            ->count();
        $sectionNumber = $course->code . '-' . ($existingCount + 1);

        $section = Section::create([
            'course_id' => $course->id,
            'section_number' => $sectionNumber,
            'semester_id' => $semester->id,
            'instructor_id' => $instructor->id,
            'days' => $days,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'Active',
        ]);

        Log::info("SlotAssignmentService: Assigned section", [
            'section_id' => $section->id,
            'pattern' => implode('/', $days),
            'time' => $startTime
        ]);

        return $section;
    }
}
