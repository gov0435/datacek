<?php

namespace App\Filament\Resources\SessionUsers\Pages;

use App\Filament\Resources\SessionUsers\SessionUserResource;
use Filament\Resources\Pages\ListRecords;

class ListSessionUsers extends ListRecords
{
    protected static string $resource = SessionUserResource::class;
}
