<?php

namespace App\Filament\Resources\PotensiPpgs\Pages;

use App\Filament\Resources\PotensiPpgs\PotensiPpgResource;
use Filament\Resources\Pages\ListRecords;

class ListPotensiPpgs extends ListRecords
{
    protected static string $resource = PotensiPpgResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
