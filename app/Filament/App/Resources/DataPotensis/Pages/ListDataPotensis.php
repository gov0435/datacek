<?php

namespace App\Filament\App\Resources\DataPotensis\Pages;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Resources\Pages\ListRecords;

class ListDataPotensis extends ListRecords
{
    protected static string $resource = DataPotensiResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
