<?php

namespace App\Filament\Resources\Sections\Pages;

use App\Filament\Resources\Sections\SectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSection extends EditRecord
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $data = $this->data;
        $record = $this->getRecord();

        // Only check if instructor is being changed
        if (!isset($data['instructor_id']) || $data['instructor_id'] == $record->instructor_id) {
            return;
        }

        $newInstructorId = $data['instructor_id'];
        $semesterId = $data['semester_id'] ?? $record->semester_id;
        $courseId = $data['course_id'] ?? $record->course_id;

        $newInstructor = \App\Models\Instructor::find($newInstructorId);
        $semester = \App\Models\Semester::find($semesterId);
        $course = \App\Models\Course::find($courseId);

        if (!$newInstructor || !$semester || !$course) {
            return;
        }

        // 1. Load Check: Cannot assign if instructor has reached minimum load (is NOT underloaded)
        $calculator = new \App\Services\Scheduling\CreditHourCalculator();
        if (!$calculator->isUnderloaded($newInstructor, $semester)) {
            $currentCredits = $calculator->calculateTotalCredits($newInstructor, $semester);
            $minCredits = $calculator->getMinimumCredits($newInstructor, $semester);

            \Filament\Notifications\Notification::make()
                ->title('Override Failed: Load Limit Reached')
                ->body("Instructor {$newInstructor->user->name} has reached the minimum load requirement ($currentCredits / $minCredits credits). Cannot assign more courses.")
                ->danger()
                ->send();

            $this->halt();
        }

        // 2. Time Conflict Check
        $checker = new \App\Services\Scheduling\TimeConflictChecker();

        $startTime = $data['start_time'] ?? $record->start_time;
        // Calculate end time or use provided
        if (isset($data['end_time'])) {
            $endTime = $data['end_time'];
        } else {
            // Fallback calculation if only start time changed (or neither)
            $hours = $course->hours ?? 3;
            $endTime = $checker->calculateEndTime($startTime, $hours);
        }

        $days = $data['days'] ?? $record->days;

        $existingSections = $newInstructor->sections()
            ->where('semester_id', $semester->id)
            ->where('id', '!=', $record->id) // Exclude current section if we were already assigned (though we checked ID change above)
            ->get();

        if ($checker->hasConflict($days, $startTime, $endTime, $existingSections)) {
            \Filament\Notifications\Notification::make()
                ->title('Override Failed: Time Conflict')
                ->body("Instructor {$newInstructor->user->name} already has a class at this time.")
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
