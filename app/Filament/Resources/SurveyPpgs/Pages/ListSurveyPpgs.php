<?php

namespace App\Filament\Resources\SurveyPpgs\Pages;

use App\Filament\Resources\SurveyPpgs\SurveyPpgResource;
use Filament\Resources\Pages\ListRecords;

class ListSurveyPpgs extends ListRecords
{
    protected static string $resource = SurveyPpgResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
