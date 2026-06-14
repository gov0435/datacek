<?php

namespace App\Filament\Resources\SurveyPpgs;

use App\Filament\Resources\SurveyPpgs\Pages\ListSurveyPpgs;
use App\Filament\Resources\SurveyPpgs\Tables\SurveyPpgsTable;
use App\Models\SurveyPpg;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SurveyPpgResource extends Resource
{
    protected static ?string $model = SurveyPpg::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return 'Survey PPG';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Survey PPG';
    }

    public static function getModelLabel(): string
    {
        return 'Survey PPG';
    }

    public static function table(Table $table): Table
    {
        return SurveyPpgsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveyPpgs::route('/'),
        ];
    }
}
