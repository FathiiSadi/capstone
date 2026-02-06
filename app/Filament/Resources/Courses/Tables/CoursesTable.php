<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('department.name')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('hours')
                    ->numeric(),
                TextColumn::make('credits')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sections')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sections_status')
                    ->label('Limit Status')
                    ->getStateUsing(function ($record) {
                        $activeSemester = \App\Models\Semester::whereIn('status', ['Open', 'Running'])->first()
                            ?? \App\Models\Semester::orderBy('id', 'desc')->first();

                        if (!$activeSemester) {
                            return null;
                        }

                        $count = $record->sections()->where('semester_id', $activeSemester->id)->count();

                        if ($count === 0) {
                            return null;
                        }

                        return $count >= $record->sections ? 'Correct' : 'False';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Correct' => 'success',
                        'False' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Correct' => 'heroicon-m-check-circle',
                        'False' => 'heroicon-m-x-circle',
                        default => '',
                    })
                    ->placeholder('No Sections'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
