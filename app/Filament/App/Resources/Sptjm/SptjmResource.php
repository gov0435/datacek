<?php

namespace App\Filament\App\Resources\Sptjm;

use App\Filament\App\Resources\Sptjm\Pages\ListSptjms;
use App\Filament\App\Resources\Sptjm\Tables\SptjmsTable;
use App\Models\SptjmSekolah;
use App\Models\User;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SptjmResource extends Resource
{
    protected static ?string $slug = 'sptjm';

    protected static ?string $model = SptjmSekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowUp;

    protected static ?string $navigationLabel = 'SPTJM Sekolah';

    protected static string|UnitEnum|null $navigationGroup = 'Upload Dokumen';

    public static function table(Table $table): Table
    {
        return SptjmsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('unggahanValid');

        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return $query->whereRaw('1 = 0');
        }

        if (str_contains(strtolower($kabKota), 'provinsi')) {
            return $query->whereIn('sekolah_jenjang', ['SLB', 'SMA', 'SMK']);
        }

        return $query->where('sekolah_kota', $kabKota);
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->isKgtk() ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->isKgtk() ?? false;
    }

    public static function getWhitelistKabKotaHeading(): string
    {
        return static::getAuthenticatedWhitelistKabKota() ?? 'Kabkota';
    }

    public static function getKabKotaFilterOptions(): array
    {
        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return [];
        }

        return [$kabKota => $kabKota];
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
            'index' => ListSptjms::route('/'),
        ];
    }
}
