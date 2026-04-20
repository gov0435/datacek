<?php

namespace App\Filament\Resources\PotensiPpgs;

use App\Filament\Resources\PotensiPpgs\Pages\ListPotensiPpgs;
use App\Filament\Resources\PotensiPpgs\Tables\PotensiPpgsTable;
use App\Models\PotensiPpg;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PotensiPpgResource extends Resource
{
    protected static ?string $model = PotensiPpg::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Potensi PPG';

    public static function getNavigationLabel(): string
    {
        return 'Potensi PPG';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Potensi PPG';
    }

    public static function getModelLabel(): string
    {
        return 'Potensi PPG';
    }

    public static function table(Table $table): Table
    {
        return PotensiPpgsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPotensiPpgs::route('/'),
        ];
    }
}
