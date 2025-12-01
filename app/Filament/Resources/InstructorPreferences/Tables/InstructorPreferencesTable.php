<?php

namespace App\Filament\Resources\InstructorPreferences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstructorPreferencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('instructor.user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('course.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submission_time')
                    ->dateTime()
                    ->sortable(),
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
