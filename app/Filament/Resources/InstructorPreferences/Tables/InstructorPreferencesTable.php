<?php

namespace App\Filament\Resources\InstructorPreferences\Tables;

use App\Support\PreferenceTimeSlotFormatter;
use Carbon\Carbon;
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
                            $days = $slot->days ? implode(' / ', $slot->days) : 'Any Day';

                            if (!$slot->start_time) {
                                return "{$days} (Any Time)";
                            }

                            $start = Carbon::parse($slot->start_time)->format('H:i');
                            $endTime = $slot->end_time ?: PreferenceTimeSlotFormatter::calculateEndFromStart($slot->start_time);
                            $end = $endTime ? Carbon::parse($endTime)->format('H:i') : '';

                            return "{$days} ({$start} - {$end})";
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
