<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Tiptap\Nodes\Text;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('code')
                    ->unique(ignoreRecord: true),
                \Filament\Forms\Components\Toggle::make('office_hours')
                    ->label('Office Hours')
                    ->default(false),
                TextInput::make('hours')->required()->numeric(),
                TextInput::make('credits')
                    ->required()
                    ->numeric()
                    ->default(3),
                TextInput::make('sections')
                    ->required()
                    ->numeric()
                    ->default(2),
            ]);
    }
}
