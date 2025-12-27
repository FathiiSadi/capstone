<?php

namespace App\Services\Scheduling\DTOs;

use Illuminate\Support\Collection;

class AllocationResult
{
    public function __construct(
        public readonly int $totalSectionsAssigned,
        public readonly int $totalPreferencesProcessed,
        public readonly int $totalPreferencesSkipped,
        public readonly Collection $unassignedCourses,
        public readonly array $skipReasons = [],
        public readonly array $statistics = []
    ) {
    }

    public function wasSuccessful(): bool
    {
        return $this->totalSectionsAssigned > 0;
    }

    public function hasUnassignedCourses(): bool
    {
        return $this->unassignedCourses->isNotEmpty();
    }

    public function toArray(): array
    {
        return [
            'total_sections_assigned' => $this->totalSectionsAssigned,
            'total_preferences_processed' => $this->totalPreferencesProcessed,
            'total_preferences_skipped' => $this->totalPreferencesSkipped,
            'unassigned_courses' => $this->unassignedCourses->toArray(),
            'skip_reasons' => $this->skipReasons,
            'statistics' => $this->statistics,
        ];
    }
}
