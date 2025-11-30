<?php

namespace App\Filament\Resources\InstructorPreferences\Pages;

use App\Filament\Resources\InstructorPreferences\InstructorPreferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstructorPreferences extends ListRecords
{
    protected static string $resource = InstructorPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
