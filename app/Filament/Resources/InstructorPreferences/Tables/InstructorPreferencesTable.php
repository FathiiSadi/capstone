<?php

namespace App\Filament\Resources\InstructorPreferences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstructorPreferencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instructor.user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submission_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('timeSlots')
                    ->label('Time Slots')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        return $record->timeSlots->map(function ($slot) {
                            $days = is_array($slot->days) ? implode('/', $slot->days) : $slot->days;

                            if (empty($slot->start_time)) {
                                return "{$days} (Any Time)";
                            }

                            $timeLabels = collect($slot->start_time)->map(function ($time) {
                                return match ($time) {
                                    '08:30:00' => 'Morning',
                                    '11:30:00' => 'Noon',
                                    '14:30:00' => 'Afternoon',
                                    default => \Carbon\Carbon::parse($time)->format('H:i')
                                };
                            })->join(', ');

                            return "{$days} ({$timeLabels})";
                        })->join(', ');
                    })
                    ->color(function ($record) {
                        return 'info';
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
