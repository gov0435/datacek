<?php

namespace App\Filament\App\Resources\SeleksiAdmins\Pages;

use App\Filament\App\Resources\SeleksiAdmins\SeleksiAdminResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSeleksiAdmin extends ViewRecord
{
    protected static string $resource = SeleksiAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
