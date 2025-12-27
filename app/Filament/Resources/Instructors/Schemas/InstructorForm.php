<?php

namespace App\Filament\Resources\Instructors\Schemas;

use App\InstructorPosition;
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

                Select::make('position')
                    ->options(InstructorPosition::class),
                TextInput::make('min_credits')->label('Min Credits')->numeric()->required()->default(6),

                \Filament\Forms\Components\Placeholder::make('total_assigned_credits')
                    ->label('Total Assigned Credit Hours')
                    ->content(fn(?Instructor $record): string => $record ? (string) $record->sections->sum(fn($s) => $s->course->credits ?? 0) : '0'),

                \Filament\Forms\Components\Placeholder::make('load_status')
                    ->label('Load Status')
                    ->content(function (?Instructor $record): string {
                        if (!$record)
                            return 'Not available';
                        $total = $record->sections->sum(fn($s) => $s->course->credits ?? 0);
                        $min = $record->min_credits ?? 0;

                        if ($total >= $min) {
                            return '✅ Meets minimum';
                        }

                        $missing = $min - $total;
                        return "⚠️ Below minimum (needs {$missing} more credits)";
                    }),
            ]);
    }
}
