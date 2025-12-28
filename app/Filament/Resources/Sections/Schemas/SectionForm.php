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
                    ->live(),
                TextInput::make('section_number')
                    ->label('Section Number')
                    ->placeholder('e.g. S1, S2')
                    ->maxLength(20),
                Select::make('semester_id')
                    ->relationship('semester', 'name')
                    ->required(),
                Select::make('instructor_id')
                    ->relationship('instructor.user', 'name')
                    ->label('Instructor')
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
                    ->required(),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
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
