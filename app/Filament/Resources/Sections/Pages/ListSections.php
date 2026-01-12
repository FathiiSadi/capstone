<?php

namespace App\Filament\Resources\Sections\Pages;

use App\Filament\Resources\Sections\SectionResource;
use App\Filament\Actions\ImportDataAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSections extends ListRecords
{
    protected static string $resource = SectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportDataAction::make('importData', 'sections'),
            CreateAction::make(),
        ];
    }
}
