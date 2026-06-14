<?php

namespace App\Filament\App\Resources\SeleksiAdmins\Pages;

use App\Filament\App\Resources\SeleksiAdmins\SeleksiAdminResource;
use Filament\Resources\Pages\ListRecords;

class ListSeleksiAdmins extends ListRecords
{
    protected static string $resource = SeleksiAdminResource::class;

    protected static ?string $title = 'Seleksi Administrasi';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
