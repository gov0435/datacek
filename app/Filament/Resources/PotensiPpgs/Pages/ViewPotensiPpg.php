<?php

namespace App\Filament\Resources\PotensiPpgs\Pages;

use App\Filament\Resources\PotensiPpgs\PotensiPpgResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPotensiPpg extends ViewRecord
{
    protected static string $resource = PotensiPpgResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
