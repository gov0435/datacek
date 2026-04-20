<?php

namespace App\Filament\Resources\PotensiPpgs\Pages;

use App\Filament\Resources\PotensiPpgs\PotensiPpgResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPotensiPpg extends EditRecord
{
    protected static string $resource = PotensiPpgResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
