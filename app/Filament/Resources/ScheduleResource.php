<?php

namespace App\Filament\Resources;

use App\Models\Section;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Section::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'University Schedule';

    protected static ?string $modelLabel = 'Schedule';

    public static function form(Schema $schema): Schema
    {
        return \App\Filament\Resources\Sections\Schemas\SectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('semester.name')
                    ->label('Semester')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('course.code')
                    ->label('Course Code')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('course.name')
                    ->label('Course Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('course.department.name')
                    ->label('Department')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('id')
                    ->label('Section #')
                    ->sortable(),

                TextColumn::make('instructor.user.name')
                    ->label('Instructor')
                    ->sortable()
                    ->searchable()
                    ->default('Not Assigned'),

                TextColumn::make('days')
                    ->label('Day')
                    ->badge()
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),

                TextColumn::make('time_range')
                    ->label('Time')
                    ->state(fn(Section $record) => \Carbon\Carbon::parse($record->start_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($record->end_time)->format('H:i'))
                    ->sortable(['start_time']),

                TextColumn::make('course.credits')
                    ->label('C.H.')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Allocated', 'Active' => 'success',
                        'Underloaded' => 'warning',
                        'Admin Override' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('semester')
                    ->relationship('semester', 'name'),

                SelectFilter::make('department')
                    ->relationship('course.department', 'name'),

                SelectFilter::make('course')
                    ->relationship('course', 'name'),

                SelectFilter::make('instructor')
                    ->relationship('instructor.user', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Override'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time');
    }

    public static function getPages(): array
    {
        return [
            'index' => ScheduleResource\Pages\ListSchedules::route('/'),
            'timetable' => ScheduleResource\Pages\Timetable::route('/timetable'),
        ];
    }
}
