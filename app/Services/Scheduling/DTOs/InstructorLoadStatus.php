<?php

namespace App\Services\Scheduling\DTOs;

use App\Enums\LoadStatus;
use App\Models\Instructor;

class InstructorLoadStatus
{
    public function __construct(
        public readonly Instructor $instructor,
        public readonly float $totalAssignedCredits,
        public readonly float $minimumRequiredCredits,
        public readonly LoadStatus $status,
        public readonly ?string $notes = null
    ) {
    }

    public function isUnderloaded(): bool
    {
        return $this->status === LoadStatus::UNDER_MINIMUM;
    }

    public function isOverloaded(): bool
    {
        return $this->status === LoadStatus::OVER_LOADED;
    }

    public function isOk(): bool
    {
        return $this->status === LoadStatus::OK;
    }

    public function toArray(): array
    {
        return [
            'instructor_id' => $this->instructor->id,
            'instructor_name' => $this->instructor->name,
            'total_assigned_credits' => $this->totalAssignedCredits,
            'minimum_required_credits' => $this->minimumRequiredCredits,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
        ];
    }
}
