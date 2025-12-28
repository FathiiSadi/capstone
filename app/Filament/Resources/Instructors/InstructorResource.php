<?php

namespace App\Filament\Resources\Instructors;

use App\Filament\Resources\Instructors\Pages\CreateInstructor;
use App\Filament\Resources\Instructors\Pages\EditInstructor;
use App\Filament\Resources\Instructors\Pages\ListInstructors;
use App\Filament\Resources\Instructors\Schemas\InstructorForm;
use App\Filament\Resources\Instructors\Tables\InstructorsTable;
use App\Models\Instructor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstructorResource extends Resource
{

    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|\UnitEnum|null $navigationGroup = 'Academic Management';

    public static function form(Schema $schema): Schema
    {
        return InstructorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstructorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SectionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'create' => Pages\CreateInstructor::route('/create'),
            'view' => Pages\ViewInstructor::route('/{record}'),
            'edit' => Pages\EditInstructor::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
