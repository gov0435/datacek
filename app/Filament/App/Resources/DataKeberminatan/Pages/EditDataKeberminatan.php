<?php

namespace App\Filament\App\Resources\DataKeberminatan\Pages;

use App\Filament\App\Resources\DataKeberminatan\DataKeberminatanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDataKeberminatan extends EditRecord
{
    protected static string $resource = DataKeberminatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
