<?php

namespace App\Filament\Resources\ScheduleResource\Pages;

use App\Filament\Resources\ScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_schedule')
                ->label('Generate Schedule')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->form([
                    \Filament\Forms\Components\Select::make('semester_id')
                        ->label('Semester')
                        ->options(\App\Models\Semester::all()->pluck('name', 'id'))
                        ->required()
                        ->default(\App\Models\Semester::first()?->id),
                    \Filament\Forms\Components\Toggle::make('clear_existing')
                        ->label('Clear Existing Schedule')
                        ->default(true),
                    \Filament\Forms\Components\Toggle::make('enable_least_chosen')
                        ->label('Enable Least-Chosen Assignment')
                        ->helperText('Try to fill gaps for courses with no instructor preferences.')
                        ->default(true),
                ])
                ->action(function (array $data, \App\Services\Scheduling\SchedulerService $schedulerService): void {
                    $semester = \App\Models\Semester::find($data['semester_id']);

                    if (!$semester) {
                        \Filament\Notifications\Notification::make()
                            ->title('Semester not found')
                            ->danger()
                            ->send();
                        return;
                    }

                    $result = $schedulerService->generateSchedule($semester, [
                        'clear_existing' => $data['clear_existing'],
                        'enable_least_chosen' => $data['enable_least_chosen'],
                    ]);

                    if ($result->isValid) {
                        \Filament\Notifications\Notification::make()
                            ->title('Schedule Generated Successfully')
                            ->body("Assigned {$result->allocationResult->totalSectionsAssigned} sections.")
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Schedule Generation Issues')
                            ->body($result->errorMessage ?: 'View detailed report for conflicts.')
                            ->warning()
                            ->send();
                    }
                })
                ->modalHeading('Generate Semester Schedule')
                ->modalDescription('This will run the FIFO scheduling algorithm for the selected semester.')
                ->modalSubmitActionLabel('Start Generation'),

            Actions\Action::make('view_timetable')
                ->label('Visual Timetable')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(fn(): string => $this->getResource()::getUrl('timetable')),
        ];
    }
}
