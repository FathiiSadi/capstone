<?php

namespace App\Services\Scheduling\DTOs;

use Illuminate\Support\Collection;

class SchedulingResult
{
    public function __construct(
        public readonly AllocationResult $allocationResult,
        public readonly Collection $underloadedInstructors,
        public readonly bool $isValid,
        public readonly array $adminNotifications = [],
        public readonly ?string $errorMessage = null
    ) {
    }

    public function requiresAdminIntervention(): bool
    {
        return $this->underloadedInstructors->isNotEmpty() || !$this->isValid;
    }

    public function toArray(): array
    {
        return [
            'allocation' => $this->allocationResult->toArray(),
            'underloaded_instructors' => $this->underloadedInstructors->map(
                fn(InstructorLoadStatus $status) => $status->toArray()
            )->toArray(),
            'is_valid' => $this->isValid,
            'requires_admin_intervention' => $this->requiresAdminIntervention(),
            'admin_notifications' => $this->adminNotifications,
            'error_message' => $this->errorMessage,
        ];
    }
}
