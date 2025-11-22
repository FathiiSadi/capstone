<?php

namespace App\Filament\Resources\Instructors\Schemas;

use App\Models\Instructor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InstructorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                ->relationship('User', 'name')
                ->searchable()
                ->required(),

                TextInput::make('position')->required()->default('Instructor'),
                TextInput::make('min_credits')->label('Min Credits')->numeric()->required()->default(6),


            ]);
    }
}
