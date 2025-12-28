<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('building')
                    ->maxLength(255),
                TextInput::make('capacity')
                    ->required()
                    ->numeric()
                    ->default(30),
                Select::make('type')
                    ->options([
                        'Lecture' => 'Lecture',
                        'Lab' => 'Lab',
                        'Office' => 'Office',
                    ])
                    ->required()
                    ->default('Lecture'),
            ]);
    }
}
