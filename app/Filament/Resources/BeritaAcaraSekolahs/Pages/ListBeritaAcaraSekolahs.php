<?php

namespace App\Filament\Resources\BeritaAcaraSekolahs\Pages;

use App\Filament\Resources\BeritaAcaraSekolahs\BeritaAcaraSekolahResource;
use Filament\Resources\Pages\ListRecords;

class ListBeritaAcaraSekolahs extends ListRecords
{
    protected static string $resource = BeritaAcaraSekolahResource::class;

    protected static ?string $title = 'Berita Acara Sekolah';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
