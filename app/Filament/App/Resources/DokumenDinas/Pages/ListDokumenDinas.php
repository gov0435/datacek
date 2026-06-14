<?php

namespace App\Filament\App\Resources\DokumenDinas\Pages;

use App\Filament\App\Resources\DokumenDinas\DokumenDinasResource;
use Filament\Resources\Pages\ListRecords;

class ListDokumenDinas extends ListRecords
{
    protected static string $resource = DokumenDinasResource::class;

    protected static ?string $title = 'Dokumen Dinas';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
