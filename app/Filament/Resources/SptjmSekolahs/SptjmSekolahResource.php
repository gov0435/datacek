<?php

namespace App\Filament\Resources\SptjmSekolahs;

use App\Filament\Resources\SptjmSekolahs\Pages\ListSptjmSekolahs;
use App\Filament\Resources\SptjmSekolahs\Tables\SptjmSekolahsTable;
use App\Models\SptjmSekolah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SptjmSekolahResource extends Resource
{
    protected static ?string $model = SptjmSekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'SPTJM Sekolah';

    protected static ?string $recordTitleAttribute = 'sekolah_npsn';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return SptjmSekolahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSptjmSekolahs::route('/'),
        ];
    }
}
