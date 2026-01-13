<?php

namespace App\Filament\Resources\Sections\Pages;

use App\Filament\Resources\Sections\SectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;

    protected function beforeCreate(): void
    {
        try {
            (new \App\Services\Scheduling\SectionValidator())->validate($this->data);
        } catch (\Exception $e) {
            $this->halt();
        }
    }
}
