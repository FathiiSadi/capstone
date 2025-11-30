<?php

namespace App\Filament\Resources\Semesters\Schemas;

use App\Models\Semester;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SemesterInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('preferences_open_at')
                    ->dateTime(),
                TextEntry::make('preferences_closed_at')
                    ->dateTime(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Semester $record): bool => $record->trashed()),
                TextEntry::make('deleted_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('ip_address')
                    ->placeholder('-'),
                TextEntry::make('user_agent')
                    ->placeholder('-'),
            ]);
    }
}
