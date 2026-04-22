<?php

namespace App\Filament\App\Resources\DataPotensis\Pages;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use App\Filament\Widgets\StatusPPGDistribution;
use Filament\Resources\Pages\ListRecords;

class ListDataPotensis extends ListRecords
{
    protected static string $resource = DataPotensiResource::class;

    protected static ?string $title = 'Data Potensi';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatusPPGDistribution::class,
        ];
    }
}
