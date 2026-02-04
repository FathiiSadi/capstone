<?php

namespace App\Filament\Resources\InstructorPreferences\Schemas;

use App\Support\PreferenceTimeSlotFormatter;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class InstructorPreferenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(function () {
                        return \App\Models\Instructor::join('users', 'instructors.user_id', '=', 'users.id')
                            ->pluck('users.name', 'instructors.id');
                    })
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('course_id')
                    ->relationship('course', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('semester_id')
                    ->relationship('semester', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                DateTimePicker::make('submission_time')
                    ->required()
                    ->default(now()),
                \Filament\Forms\Components\Repeater::make('timeSlots')
                    ->relationship('timeSlots')
                    ->schema([
                        Select::make('days')
                            ->options([
                                'Sunday' => 'Sunday',
                                'Monday' => 'Monday',
                                'Tuesday' => 'Tuesday',
                                'Wednesday' => 'Wednesday',
                                'Thursday' => 'Thursday',
                                'Saturday' => 'Saturday',
                            ])
                            ->multiple()
                            ->required(),
                        Select::make('start_time')
                            ->label('Time Slot')
                            ->options([
                                '08:30:00' => 'Morning (08:30 - 11:30)',
                                '11:30:00' => 'Noon (11:30 - 14:30)',
                                '14:30:00' => 'Afternoon (14:30 - 17:30)',
                            ])
                            ->required()
                            ->live()
                            ->afterStateHydrated(function ($state, callable $set) {
                                $set('end_time', PreferenceTimeSlotFormatter::calculateEndFromStart($state));
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('end_time', PreferenceTimeSlotFormatter::calculateEndFromStart($state));
                            }),
                        Hidden::make('end_time')
                            ->dehydrated()
                            ->default(fn($get) => PreferenceTimeSlotFormatter::calculateEndFromStart($get('start_time'))),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
