<?php

namespace App\Filament\Resources\Sections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'name')
                    ->required()
                    ->live()
                    ->disabled(),
                TextInput::make('section_number')
                    ->label('Section Number')
                    ->placeholder('e.g. S1, S2')
                    ->maxLength(20)
                    ->disabled(),
                Select::make('semester_id')
                    ->relationship('semester', 'name')
                    ->required()
                    ->disabled(),
                Select::make('instructor_id')
                    ->label('Instructor')
                    ->options(function () {
                        return \App\Models\Instructor::join('users', 'instructors.user_id', '=', 'users.id')
                            ->pluck('users.name', 'instructors.id');
                    })
                    ->searchable()
                    ->placeholder('Select Instructor (Optional)'),
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
                    ->required()
                    ->disabled(),
                TimePicker::make('start_time')
                    ->required()
                    ->disabled(),
                TimePicker::make('end_time')
                    ->required()
                    ->disabled(),
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->label('Room')
                    ->placeholder('Select Room (Optional)')
                    ->preload()
                    ->searchable()
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Allocated' => 'Allocated',
                        'Underloaded' => 'Underloaded',
                        'Admin Override' => 'Admin Override',
                        'Inactive' => 'Inactive',
                        'Closed' => 'Closed',
                    ])
                    ->default('Active')
                    ->required(),
            ]);
    }
}
