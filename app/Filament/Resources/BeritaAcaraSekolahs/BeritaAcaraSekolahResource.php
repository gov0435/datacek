<?php

namespace App\Filament\Resources\BeritaAcaraSekolahs;

use App\Filament\Resources\BeritaAcaraSekolahs\Pages\ListBeritaAcaraSekolahs;
use App\Filament\Resources\BeritaAcaraSekolahs\Tables\BeritaAcaraSekolahsTable;
use App\Models\BeritaAcaraSekolah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BeritaAcaraSekolahResource extends Resource
{
    protected static ?string $model = BeritaAcaraSekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Berita Acara Sekolah';

    protected static ?string $recordTitleAttribute = 'sekolah_npsn';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return BeritaAcaraSekolahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBeritaAcaraSekolahs::route('/'),
        ];
    }
}
