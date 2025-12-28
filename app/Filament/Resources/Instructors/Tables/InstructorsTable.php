<?php

namespace App\Filament\Resources\Instructors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InstructorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\IconColumn::make('load_status')
                    ->label('Load Met')
                    ->getStateUsing(function (\App\Models\Instructor $record) {
                        $totalCredits = $record->sections->sum(fn($s) => $s->course->credits ?? 0);
                        if ($totalCredits == 0)
                            return null;
                        return $totalCredits >= $record->min_credits;
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')->trueColor('success')
                    ->falseIcon('heroicon-o-x-circle')->falseColor('danger')
                    ->placeholder('Hold'),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('min_credits')
                    ->sortable(),


            ])
            ->filters([
                //                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
