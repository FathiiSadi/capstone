<?php

namespace App\Filament\Widgets;

use App\Models\Section;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAssignments extends BaseWidget
{
    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Section::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('course.code')
                    ->label('Course')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('instructor.user.name')
                    ->label('Instructor'),
                Tables\Columns\TextColumn::make('section_number')
                    ->label('Section')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Assigned At')
                    ->dateTime()
                    ->sortable(),
            ]);
    }

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
