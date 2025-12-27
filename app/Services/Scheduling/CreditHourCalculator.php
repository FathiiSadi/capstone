<?php

namespace App\Services\Scheduling;

use App\Enums\LoadStatus;
use App\Models\Instructor;
use App\Models\Semester;
use App\Services\Scheduling\DTOs\InstructorLoadStatus;
use Illuminate\Support\Collection;

class CreditHourCalculator
{
    /**
     * Calculate total assigned credit hours for an instructor in a semester.
     */
    public function calculateTotalCredits(Instructor $instructor, Semester $semester): float
    {
        return $instructor->sections()
            ->where('semester_id', $semester->id)
            ->with('course')
            ->get()
            ->sum(function ($section) {
                return $section->course->credits ?? 0;
            });
    }

    /**
     * Check if an instructor is underloaded for a semester.
     */
    public function isUnderloaded(Instructor $instructor, Semester $semester): bool
    {
        $totalCredits = $this->calculateTotalCredits($instructor, $semester);
        $minimumCredits = $this->getMinimumCredits($instructor, $semester);

        return $totalCredits < $minimumCredits;
    }

    /**
     * Get minimum required credit hours for an instructor.
     * Uses the instructor's min_credits field.
     */
    public function getMinimumCredits(Instructor $instructor, Semester $semester): float
    {
        // Use the instructor's min_credits field if set
        // Fall back to default based on position if not set
        if ($instructor->min_credits !== null && $instructor->min_credits > 0) {
            return (float) $instructor->min_credits;
        }

        // Default minimums based on position (fallback)
        return $this->getDefaultMinimumByPosition($instructor->position);
    }

    /**
     * Get maximum allowed credit hours for an instructor.
     */
    public function getMaxCredits(Instructor $instructor): float
    {
        // Standard academic maximum cap
        return 18.0;
    }

    /**
     * Get default minimum credit hours based on instructor position.
     */
    protected function getDefaultMinimumByPosition($position): float
    {
        return match ($position) {
            \App\InstructorPosition::Instructor => 12.0,
            \App\InstructorPosition::Doctor => 12.0,
            \App\InstructorPosition::Lecturer => 12.0,
            \App\InstructorPosition::POP => 12.0,
            \App\InstructorPosition::HOD => 9.0,
            \App\InstructorPosition::Dean => 6.0,
            \App\InstructorPosition::TA => 6.0,
            default => 12.0,
        };
    }

    /**
     * Get load status for an instructor.
     */
    public function getLoadStatus(Instructor $instructor, Semester $semester): InstructorLoadStatus
    {
        $totalCredits = $this->calculateTotalCredits($instructor, $semester);
        $minimumCredits = $this->getMinimumCredits($instructor, $semester);

        $status = $this->determineStatus($totalCredits, $minimumCredits);
        $notes = $this->generateNotes($totalCredits, $minimumCredits, $status);

        return new InstructorLoadStatus(
            instructor: $instructor,
            totalAssignedCredits: $totalCredits,
            minimumRequiredCredits: $minimumCredits,
            status: $status,
            notes: $notes
        );
    }

    /**
     * Get all underloaded instructors for a semester.
     */
    public function getUnderloadedInstructors(Semester $semester): Collection
    {
        // Get all instructors who have preferences for this semester
        $instructorIds = \App\Models\InstructorPreference::query()
            ->where('semester_id', $semester->id)
            ->distinct()
            ->pluck('instructor_id');

        $underloaded = collect();

        foreach ($instructorIds as $instructorId) {
            $instructor = Instructor::find($instructorId);
            if (!$instructor) {
                continue;
            }

            $loadStatus = $this->getLoadStatus($instructor, $semester);
            if ($loadStatus->isUnderloaded()) {
                $underloaded->push($loadStatus);
            }
        }

        return $underloaded;
    }

    /**
     * Determine load status based on credits.
     */
    protected function determineStatus(float $totalCredits, float $minimumCredits): LoadStatus
    {
        if ($totalCredits < $minimumCredits) {
            return LoadStatus::UNDER_MINIMUM;
        }

        // Optional: set a maximum threshold (e.g., 150% of minimum)
        $maximumCredits = $minimumCredits * 1.5;
        if ($totalCredits > $maximumCredits) {
            return LoadStatus::OVER_LOADED;
        }

        return LoadStatus::OK;
    }

    /**
     * Generate notes explaining the load status.
     */
    protected function generateNotes(float $totalCredits, float $minimumCredits, LoadStatus $status): ?string
    {
        return match ($status) {
            LoadStatus::UNDER_MINIMUM => sprintf(
                'Short by %.1f credit hours (assigned: %.1f, required: %.1f)',
                $minimumCredits - $totalCredits,
                $totalCredits,
                $minimumCredits
            ),
            LoadStatus::OVER_LOADED => sprintf(
                'Exceeds recommended maximum by %.1f credit hours',
                $totalCredits - ($minimumCredits * 1.5)
            ),
            LoadStatus::OK => null,
        };
    }
}
