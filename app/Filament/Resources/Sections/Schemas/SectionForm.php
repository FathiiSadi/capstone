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
                    ->required(),
                Select::make('semester_id')
                    ->relationship('semester', 'name')
                    ->required(),
                Select::make('days')
                    ->options([
            'Sunday' => 'Sunday',
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Saturday' => 'Saturday',
        ])
                    ->required(),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
                Select::make('status')
                    ->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Closed' => 'Closed'])
                    ->default('Active')
                    ->required(),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
                TextInput::make('deleted_by')
                    ->numeric(),
                TextInput::make('ip_address'),
                TextInput::make('user_agent'),
            ]);
    }
}
