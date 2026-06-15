<?php

namespace App\Filament\App\Resources\Sptjm\Pages;

use App\Filament\App\Resources\Sptjm\SptjmResource;
use Filament\Resources\Pages\ListRecords;

class ListSptjms extends ListRecords
{
    protected static string $resource = SptjmResource::class;

    protected static ?string $title = 'SPTJM';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
