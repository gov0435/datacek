<?php

namespace App\Filament\App\Resources\DataPotensis\Pages;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDataPotensi extends EditRecord
{
    protected static string $resource = DataPotensiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
