<?php

namespace App\Filament\Resources\InstructorPreferences;

use App\Filament\Resources\InstructorPreferences\Pages\CreateInstructorPreference;
use App\Filament\Resources\InstructorPreferences\Pages\EditInstructorPreference;
use App\Filament\Resources\InstructorPreferences\Pages\ListInstructorPreferences;
use App\Filament\Resources\InstructorPreferences\Schemas\InstructorPreferenceForm;
use App\Filament\Resources\InstructorPreferences\Tables\InstructorPreferencesTable;
use App\Models\InstructorPreference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstructorPreferenceResource extends Resource
{
    protected static ?string $model = InstructorPreference::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';

    public static function form(Schema $schema): Schema
    {
        return InstructorPreferenceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstructorPreferencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructorPreferences::route('/'),
            'create' => CreateInstructorPreference::route('/create'),
            'edit' => EditInstructorPreference::route('/{record}/edit'),
        ];
    }
}
