<?php

namespace App\Filament\Resources\Whitelists;

use App\Filament\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Resources\Whitelists\Pages\EditWhitelist;
use App\Filament\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Resources\Whitelists\Schemas\WhitelistForm;
use App\Filament\Resources\Whitelists\Tables\WhitelistsTable;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return 'Whitelist';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Whitelist';
    }

    public static function getModelLabel(): string
    {
        return 'Whitelist';
    }

    public static function form(Schema $schema): Schema
    {
        return WhitelistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhitelists::route('/'),
            'create' => CreateWhitelist::route('/create'),
            'edit' => EditWhitelist::route('/{record}/edit'),
        ];
    }
}
