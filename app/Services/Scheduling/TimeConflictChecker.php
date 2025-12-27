<?php

namespace App\Services\Scheduling;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimeConflictChecker
{
    /**
     * Check if assigning a section at the given time would cause a conflict
     * with the instructor's existing sections.
     */
    public function hasConflict(
        string|array $days,
        string $startTime,
        string $endTime,
        Collection $existingSections
    ): bool {
        $days = is_array($days) ? $days : [$days];

        foreach ($days as $day) {
            // Filter sections for the same day
            $sameDaySections = $existingSections->filter(function ($section) use ($day) {
                // Handle both string and array days format
                $sectionDays = is_array($section->days) ? $section->days : [$section->days];
                return in_array($day, $sectionDays);
            });

            // Check for time overlaps
            foreach ($sameDaySections as $section) {
                if ($this->timesOverlap($startTime, $endTime, $section->start_time, $section->end_time)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the corresponding pair day for a given day.
     */
    public function getDayPair(string $day): array
    {
        $pairs = [
            'Sunday' => ['Sunday', 'Wednesday'],
            'Wednesday' => ['Sunday', 'Wednesday'],
            'Monday' => ['Monday', 'Thursday'],
            'Thursday' => ['Monday', 'Thursday'],
            'Tuesday' => ['Tuesday', 'Saturday'],
            'Saturday' => ['Tuesday', 'Saturday'],
        ];

        return $pairs[$day] ?? [$day];
    }

    /**
     * Calculate end time based on start time and duration in hours.
     * Supports decimals (e.g., 1.5 = 1 hour 30 mins).
     */
    public function calculateEndTime(string $startTime, float $hours): string
    {
        return Carbon::parse($startTime)
            ->addMinutes((int) ($hours * 60))
            ->format('H:i:s');
    }

    /**
     * Check if two time ranges overlap.
     */
    public function timesOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        $start1 = Carbon::parse($start1);
        $end1 = Carbon::parse($end1);
        $start2 = Carbon::parse($start2);
        $end2 = Carbon::parse($end2);

        // Two ranges overlap if:
        // start1 < end2 AND start2 < end1
        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Validate that a time slot falls within the teaching day (08:30 - 17:30).
     */
    public function isWithinTeachingDay(string $startTime, string $endTime): bool
    {
        $dayStart = Carbon::parse('08:30:00');
        $dayEnd = Carbon::parse('17:30:00');

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        return $start->gte($dayStart) && $end->lte($dayEnd);
    }

    /**
     * Get all time conflicts for multiple sections on the same day.
     */
    public function getConflicts(Collection $sections, string $day): Collection
    {
        $conflicts = collect();
        $daySections = $sections->filter(function ($section) use ($day) {
            $sectionDays = is_array($section->days) ? $section->days : [$section->days];
            return in_array($day, $sectionDays);
        })->values();

        for ($i = 0; $i < $daySections->count(); $i++) {
            for ($j = $i + 1; $j < $daySections->count(); $j++) {
                if (
                    $this->timesOverlap(
                        $daySections[$i]->start_time,
                        $daySections[$i]->end_time,
                        $daySections[$j]->start_time,
                        $daySections[$j]->end_time
                    )
                ) {
                    $conflicts->push([
                        'section1' => $daySections[$i],
                        'section2' => $daySections[$j],
                    ]);
                }
            }
        }

        return $conflicts;
    }
}
