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
                    ->relationship('instructor.user', 'name')
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
                    ->required(),
            ]);
    }
}
