<?php

namespace App\Filament\Resources\Semesters\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SemesterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options(['Fall' => 'Fall', 'Spring' => 'Spring', 'Summer' => 'Summer'])
                    ->default('Fall')
                    ->required(),
                DateTimePicker::make('preferences_open_at')
                    ->required(),
                DateTimePicker::make('preferences_closed_at')
                    ->required(),
                Select::make('status')
                    ->options(['Draft' => 'Draft', 'Open' => 'Open', 'Running' => 'Running', 'closed' => 'Closed'])
                    ->default('closed')
                    ->required(),
            ]);
    }
}
