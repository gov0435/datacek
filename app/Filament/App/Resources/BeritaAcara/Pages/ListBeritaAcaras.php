<?php

namespace App\Filament\App\Resources\BeritaAcara\Pages;

use App\Filament\App\Resources\BeritaAcara\BeritaAcaraResource;
use Filament\Resources\Pages\ListRecords;

class ListBeritaAcaras extends ListRecords
{
    protected static string $resource = BeritaAcaraResource::class;

    protected static ?string $title = 'Berita Acara';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
