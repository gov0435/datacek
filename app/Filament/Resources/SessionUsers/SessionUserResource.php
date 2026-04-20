<?php

namespace App\Filament\Resources\SessionUsers;

use App\Filament\Resources\SessionUsers\Pages\ListSessionUsers;
use App\Filament\Resources\SessionUsers\Tables\SessionUsersTable;
use App\Models\SessionUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SessionUserResource extends Resource
{
    protected static ?string $model = SessionUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationLabel(): string
    {
        return 'Session Users';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Session Users';
    }

    public static function getModelLabel(): string
    {
        return 'Session User';
    }

    public static function table(Table $table): Table
    {
        return SessionUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSessionUsers::route('/'),
        ];
    }
}
