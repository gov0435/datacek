<?php

namespace App\Filament\App\Resources\DataKeberminatan\Pages;

use App\Filament\App\Resources\DataKeberminatan\DataKeberminatanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDataKeberminatan extends ViewRecord
{
    protected static string $resource = DataKeberminatanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
