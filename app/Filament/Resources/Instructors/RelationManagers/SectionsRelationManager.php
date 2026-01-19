<?php

namespace App\Filament\Resources\Instructors\RelationManagers;

use App\Models\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    protected static ?string $title = 'Assigned Schedule';

    public function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\Sections\Schemas\SectionForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('course.code')
                    ->label('Course Code')
                    ->searchable(),

                TextColumn::make('course.name')
                    ->label('Course Name')
                    ->searchable(),

                TextColumn::make('section_number')
                    ->label('Section #'),

                TextColumn::make('days')
                    ->label('Day')
                    ->badge(),

                TextColumn::make('time_range')
                    ->label('Time')
                    ->state(fn(Section $record) => \Carbon\Carbon::parse($record->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($record->end_time)->format('H:i')),

                TextColumn::make('course.credits')
                    ->label('C.H.')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make()
                    ->before(function (array $data, Section $record) {
                        try {
                            (new \App\Services\Scheduling\SectionValidator())->validate($data, $record);
                        } catch (\Exception $e) {
                            throw new \Filament\Support\Exceptions\Halt();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
