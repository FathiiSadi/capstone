<?php

namespace App\Services\Scheduling;

use App\Models\Instructor;
use App\Models\Section;
use App\Models\Semester;
use App\Models\Course;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class SectionValidator
{
    protected CreditHourCalculator $calculator;
    protected TimeConflictChecker $conflictChecker;

    public function __construct()
    {
        $this->calculator = new CreditHourCalculator();
        $this->conflictChecker = new TimeConflictChecker();
    }

    /**
     * Validate a section assignment or update.
     * 
     * @param array $data The new data being saved
     * @param Section|null $record The existing record (if updating)
     * @return bool Returns true if valid, throws exception or returns false if invalid
     * @throws \Exception
     */
    public function validate(array $data, ?Section $record = null): void
    {
        $instructorId = $data['instructor_id'] ?? $record?->instructor_id;
        $semesterId = $data['semester_id'] ?? $record?->semester_id;
        $courseId = $data['course_id'] ?? $record?->course_id;

        if (!$instructorId || !$semesterId || !$courseId) {
            return;
        }

        $instructor = Instructor::find($instructorId);
        $semester = Semester::find($semesterId);
        $course = Course::find($courseId);

        if (!$instructor || !$semester || !$course) {
            return;
        }

        // 1. Load Check
        // Only check if we are assigning a NEW instructor (different from current)
        // or if it's a new record and an instructor is assigned.
        $instructorChanged = !$record
            ? ($instructorId !== null)
            : (isset($data['instructor_id']) && $data['instructor_id'] != $record->instructor_id);

        if ($instructorChanged && $instructorId) {
            if (!$this->calculator->isUnderloaded($instructor, $semester)) {
                $currentCredits = $this->calculator->calculateTotalCredits($instructor, $semester);
                $minCredits = $this->calculator->getMinimumCredits($instructor, $semester);

                Notification::make()
                    ->title('Assignment Warning: Load Limit')
                    ->body("Instructor {$instructor->user->name} has reached the minimum load requirement ($currentCredits / $minCredits credits).")
                    ->warning()
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }
        }

        // 2. Time Conflict Check
        // Check if instructor, days, or time changed
        $days = $data['days'] ?? $record?->days;
        $startTime = $data['start_time'] ?? $record?->start_time;

        // Calculate end time
        if (isset($data['end_time'])) {
            $endTime = $data['end_time'];
        } else {
            $hours = $course->hours ?? 3;
            $endTime = $this->conflictChecker->calculateEndTime($startTime, $hours);
        }

        $daysChanged = isset($data['days']) && (!$record || $data['days'] != $record->days);
        $timeChanged = isset($data['start_time']) && (!$record || $data['start_time'] != $record->start_time);
        $endTimeChanged = isset($data['end_time']) && (!$record || $data['end_time'] != $record->end_time);

        if ($instructorChanged || $daysChanged || $timeChanged || $endTimeChanged) {
            $existingSections = $instructor->sections()
                ->where('semester_id', $semester->id);

            if ($record) {
                $existingSections->where('id', '!=', $record->id);
            }

            $existingSections = $existingSections->get();

            if ($this->conflictChecker->hasConflict($days, $startTime, $endTime, $existingSections)) {
                Notification::make()
                    ->title('Assignment Warning: Time Conflict')
                    ->body("Instructor {$instructor->user->name} already has a class at this time.")
                    ->warning()
                    ->send();

                throw new \Filament\Support\Exceptions\Halt();
            }
        }
    }
}
