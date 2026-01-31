<?php

namespace App\Filament\Resources\InstructorPreferences\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                        \Filament\Forms\Components\Select::make('start_time')
                            ->label('Time Slot')
                            ->options([
                                '08:30:00' => 'Morning (8:30 - 11:30)',
                                '11:30:00' => 'Noon (11:30 - 2:30)',
                                '14:30:00' => 'Afternoon (2:30 - 5:30)',
                            ])
                            ->multiple(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
