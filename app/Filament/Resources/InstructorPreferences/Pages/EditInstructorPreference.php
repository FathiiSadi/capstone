<?php

namespace App\Filament\Resources\InstructorPreferences\Pages;

use App\Filament\Resources\InstructorPreferences\InstructorPreferenceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstructorPreference extends EditRecord
{
    protected static string $resource = InstructorPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
