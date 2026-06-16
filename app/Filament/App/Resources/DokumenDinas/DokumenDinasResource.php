<?php

namespace App\Filament\App\Resources\DokumenDinas;

use App\Filament\App\Resources\DokumenDinas\Pages\ListDokumenDinas;
use App\Filament\App\Resources\DokumenDinas\Tables\DokumenDinasTable;
use App\Models\DokumenDinas;
use App\Models\User;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class DokumenDinasResource extends Resource
{
    protected static ?string $slug = 'dokumen-dinas';

    protected static ?string $model = DokumenDinas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderArrowDown;

    protected static ?string $navigationLabel = 'Dokumen Dinas';

    protected static string|UnitEnum|null $navigationGroup = 'Upload Dokumen';

    public static function table(Table $table): Table
    {
        return DokumenDinasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $kabkota = static::getAuthenticatedWhitelistKabKota();

        if ($kabkota === null) {
            return DokumenDinas::query()->whereRaw('1 = 0');
        }

        return DokumenDinas::query()
            ->where('kabkota', $kabkota)
            ->whereIn('id', function ($query) use ($kabkota) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('dokumen_dinas')
                    ->where('kabkota', $kabkota)
                    ->groupBy('jenis');
            });
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isKgtk() || Auth::user()?->isMember();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isKgtk() || Auth::user()?->isMember();
    }

    public static function getWhitelistKabKotaHeading(): string
    {
        return static::getAuthenticatedWhitelistKabKota() ?? 'Kabkota';
    }

    public static function getAuthenticatedWhitelistKabKota(): ?string
    {
        $whitelist = static::getAuthenticatedWhitelist();

        if ($whitelist === null) {
            return null;
        }

        return static::extractKabKotaValue($whitelist);
    }

    private static function extractKabKotaValue(Whitelist $model): ?string
    {
        $enumValue = $model->kabkota?->value;

        if (is_string($enumValue) && $enumValue !== '') {
            return $enumValue;
        }

        $rawValue = $model->getRawOriginal('kabkota');

        if (is_string($rawValue) && $rawValue !== '') {
            return $rawValue;
        }

        return null;
    }

    private static function getAuthenticatedWhitelist(): ?Whitelist
    {
        return once(function (): ?Whitelist {
            $user = Auth::user();

            if (! $user instanceof User) {
                return null;
            }

            return Whitelist::query()
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $user->email)])
                ->first();
        });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDokumenDinas::route('/'),
        ];
    }
}
