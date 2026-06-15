<?php

namespace App\Filament\Resources\SptjmSekolahs\Pages;

use App\Filament\Resources\SptjmSekolahs\SptjmSekolahResource;
use Filament\Resources\Pages\ListRecords;

class ListSptjmSekolahs extends ListRecords
{
    protected static string $resource = SptjmSekolahResource::class;

    protected static ?string $title = 'SPTJM Sekolah';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
