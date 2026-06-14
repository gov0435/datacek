<?php

namespace App\Filament\App\Resources\SeleksiAdmins;

use App\Enums\VervalStatus;
use App\Filament\App\Resources\SeleksiAdmins\Pages\ListSeleksiAdmins;
use App\Filament\App\Resources\SeleksiAdmins\Tables\SeleksiAdminsTable;
use App\Models\SurveyPpg;
use App\Models\User;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SeleksiAdminResource extends Resource
{
    protected static ?string $slug = 'seleksi-admin';

    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP', 'Lainnya'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

    protected static ?string $model = SurveyPpg::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Seleksi Administrasi';

    public static function table(Table $table): Table
    {
        return SeleksiAdminsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->whereNotNull('ptk_id')
            ->where('has_verval', true)
            ->where('verval_status', VervalStatus::Disetujui->value)
            ->whereIn('sekolah_jenjang', static::getAllowedJenjangValues());

        if (static::isProvinsiScope()) {
            return $query;
        }

        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('sekolah_kota', $kabKota);
    }

    public static function getKabKotaFilterOptions(): array
    {
        if (static::isProvinsiScope()) {
            return [];
        }

        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if ($kabKota === null) {
            return [];
        }

        return [$kabKota => $kabKota];
    }

    public static function isProvinsiScope(): bool
    {
        $kabKota = static::getAuthenticatedWhitelistKabKota();

        if (is_string($kabKota) && str_contains(strtolower($kabKota), 'provinsi')) {
            return true;
        }

        $scopeLabel = static::getScopeLabelForAuthenticatedUser();

        return $scopeLabel !== null && str_contains(strtolower($scopeLabel), 'provinsi');
    }

    public static function getAllowedJenjangValues(): array
    {
        if (static::isProvinsiScope()) {
            return self::JENJANG_PROVINSI;
        }

        return self::JENJANG_KAB_KOTA;
    }

    public static function getWhitelistKabKotaHeading(): string
    {
        return static::getAuthenticatedWhitelistKabKota() ?? 'Kabkota';
    }

    private static function getAuthenticatedWhitelistKabKota(): ?string
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

    private static function getScopeLabelForAuthenticatedUser(): ?string
    {
        $whitelist = static::getAuthenticatedWhitelist();

        $whitelistScope = $whitelist?->instansi;

        if (is_string($whitelistScope) && $whitelistScope !== '') {
            return $whitelistScope;
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeleksiAdmins::route('/'),
        ];
    }
}
