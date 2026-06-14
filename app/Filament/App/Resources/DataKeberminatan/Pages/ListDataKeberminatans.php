<?php

namespace App\Filament\App\Resources\DataKeberminatan\Pages;

use App\Filament\App\Resources\DataKeberminatan\DataKeberminatanResource;
use Filament\Resources\Pages\ListRecords;

class ListDataKeberminatans extends ListRecords
{
    protected static string $resource = DataKeberminatanResource::class;

    protected static ?string $title = 'Data Keberminatan';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
