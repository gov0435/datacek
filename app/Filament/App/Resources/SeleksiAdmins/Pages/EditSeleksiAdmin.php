<?php

namespace App\Filament\App\Resources\SeleksiAdmins\Pages;

use App\Filament\App\Resources\SeleksiAdmins\SeleksiAdminResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSeleksiAdmin extends EditRecord
{
    protected static string $resource = SeleksiAdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
