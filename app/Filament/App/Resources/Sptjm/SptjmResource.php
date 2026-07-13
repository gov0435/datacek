<?php

namespace App\Filament\App\Resources\Sptjm;

use App\Enums\Jenjang;
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
    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP', 'Lainnya'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

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
        $query = parent::getEloquentQuery()->with(['unggahanValid', 'unggahan']);

        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return $query->whereRaw('1 = 0');
        }

        if (str_contains(strtolower($kabKota), 'provinsi')) {
            return $query->where('scope', 'provinsi');
        }

        return $query->where('scope', 'kabkota')
            ->where('sekolah_kota', $kabKota);
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

    public static function getKabKotaFilterOptions(): array
    {
        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return [];
        }

        return [$kabKota => $kabKota];
    }

    public static function isProvinsiScope(): bool
    {
        $kabKota = static::getAuthenticatedWhitelistKabKota();

        return $kabKota !== null && str_contains(strtolower($kabKota), 'provinsi');
    }

    public static function getAllowedJenjangValues(): array
    {
        if (static::isProvinsiScope()) {
            return self::JENJANG_PROVINSI;
        }

        return self::JENJANG_KAB_KOTA;
    }

    public static function getJenjangFilterOptions(): array
    {
        $allowed = static::getAllowedJenjangValues();

        return collect(Jenjang::cases())
            ->filter(fn (Jenjang $jenjang): bool => in_array($jenjang->value, $allowed, true))
            ->mapWithKeys(fn (Jenjang $jenjang): array => [$jenjang->value => $jenjang->value])
            ->all();
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
