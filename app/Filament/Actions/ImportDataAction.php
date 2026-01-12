<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class ImportDataAction extends Action
{
    public static function make(?string $name = 'importData', ?string $resourceType = 'courses'): static
    {
        return parent::make($name)
            ->label('Import Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('file')
                    ->label('Excel File')
                    ->disk('local')
                    ->directory('imports')
                    ->required(),
            ])
            ->extraModalFooterActions([
                Action::make('downloadTemplate')
                    ->label('Download Template')
                    ->color('info')
                    ->action(function () use ($resourceType) {
                        $headers = match ($resourceType) {
                            'users' => [
                                'Name',
                                'Email',
                                'Role',
                                'Department',
                                'Position',
                                'Min Credits'
                            ],
                            'sections' => [
                                'Course Code',
                                'Section Number',
                                'Semester',
                                'Instructor Name',
                                'Days',
                                'Start Time',
                                'End Time',
                                'Room',
                                'Status'
                            ],
                            'rooms' => [
                                'Name',
                                'Building',
                                'Capacity',
                                'Type'
                            ],
                            'departments' => [
                                'Name',
                                'Code',
                                'Manager Name'
                            ],
                            default => [ // courses
                                'College',
                                'Course Number',
                                'Course Name',
                                'CRS_NO',
                                'Section Number',
                                'Theoretical',
                                'Hours',
                                'Section Capacity',
                                'No of registered',
                                'Instructor Name',
                                'Second Instructor',
                                'Notes',
                                'Time / Classroom',
                                'pre-requisite',
                                'Course Type',
                                'For new admitted students only'
                            ],
                        };

                        $filename = str_replace(' ', '_', strtolower($resourceType)) . '_template.csv';

                        return response()->streamDownload(function () use ($headers) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, $headers);
                            fclose($handle);
                        }, $filename);
                    }),
            ])
            ->action(function (array $data) {
                $filePath = storage_path('app/imports/' . basename($data['file']));

                try {
                    Artisan::call('app:import-courses', ['file' => $filePath]);

                    Notification::make()
                        ->title('Import Completed')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Import Failed')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
