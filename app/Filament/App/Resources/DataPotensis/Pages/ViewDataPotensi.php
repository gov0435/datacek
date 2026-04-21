<?php

namespace App\Filament\App\Resources\DataPotensis\Pages;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDataPotensi extends ViewRecord
{
    protected static string $resource = DataPotensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
