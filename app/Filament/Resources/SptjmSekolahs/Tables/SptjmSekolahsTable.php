<?php

namespace App\Filament\Resources\SptjmSekolahs\Tables;

use App\Enums\KabKota;
use App\Helpers\FileHelper;
use App\Models\SptjmSekolah;
use App\Models\SptjmUnggahan;
use App\Models\SurveyPpg;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SptjmSekolahsTable
{
    private const JENJANG_KAB_KOTA = ['PAUD', 'SD', 'SMP', 'Lainnya'];

    private const JENJANG_PROVINSI = ['SLB', 'SMA', 'SMK'];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sekolah_nama')
                    ->label('Sekolah')
                    ->description(fn (SptjmSekolah $record): string => 'NPSN: '.$record->sekolah_npsn)
                    ->searchable(['sekolah_nama', 'sekolah_npsn'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->searchable(),
                TextColumn::make('sekolah_kota')
                    ->label('Kota')
                    ->searchable(),
                TextColumn::make('jumlah_guru')
                    ->label('Jml Guru')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('unggahanValid.is_valid')
                    ->label('Status SPTJM')
                    ->badge()
                    ->state(fn (SptjmSekolah $record): string => static::getStatusSptjm($record))
                    ->color(fn (string $state): string => match ($state) {
                        'Valid' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                IconColumn::make('has_hardcopy')
                    ->label('Hardcopy')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('unggahanValid.file_name')
                    ->label('File')
                    ->default('-')
                    ->wrap()
                    ->tooltip(fn (SptjmSekolah $record): ?string => $record->unggahanValid?->file_name)
                    ->icon(fn ($state): ?string => $state && $state !== '-' ? 'heroicon-o-arrow-down-tray' : null)
                    ->color(fn ($state): ?string => $state && $state !== '-' ? 'primary' : null)
                    ->formatStateUsing(fn (?string $state): ?string => FileHelper::trimFileName($state))
                    ->url(function (SptjmSekolah $record): ?string {
                        $unggahan = $record->unggahanValid;

                        if ($unggahan === null) {
                            return null;
                        }

                        return Storage::disk($unggahan->disk)
                            ->temporaryUrl($unggahan->file_path, now()->addMinutes(60));
                    })
                    ->openUrlInNewTab(),
                TextColumn::make('unggahanValid.updated_at')
                    ->label('Tgl Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Generate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('generateSekolah')
                    ->label('Generate Data Sekolah')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->color('primary')
                    ->form([
                        Select::make('kabkota')
                            ->label('Kabupaten/Kota / Provinsi')
                            ->options(KabKota::class)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        static::generateSekolah($data);
                    }),
            ])
            ->filters([
                SelectFilter::make('sekolah_kota')
                    ->label('Kota')
                    ->options(KabKota::class),
                SelectFilter::make('scope')
                    ->label('Scope')
                    ->options([
                        'kabkota' => 'Kab/Kota',
                        'provinsi' => 'Provinsi',
                    ]),
                SelectFilter::make('status_sptjm')
                    ->label('Status SPTJM')
                    ->options([
                        'belum' => 'Belum Diupload',
                        'pending' => 'Pending',
                        'valid' => 'Valid',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'belum' => $query->where('is_valid', false)->whereDoesntHave('unggahan'),
                            'pending' => $query->where('is_valid', false)->whereHas('unggahan'),
                            'valid' => $query->where('is_valid', true),
                            default => $query,
                        };
                    }),
                SelectFilter::make('has_hardcopy')
                    ->label('Status Hardcopy')
                    ->options([
                        '1' => 'Hardcopy Ada',
                        '0' => 'Hardcopy Tidak Ada',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('has_hardcopy', filter_var($value, FILTER_VALIDATE_BOOLEAN));
                    }),
            ])
            ->defaultSort('sekolah_npsn')
            ->recordActions([
                static::toggleHardcopyAction(),
                static::deleteAction(),
            ])
            ->toolbarActions([]);
    }

    private static function getStatusSptjm(SptjmSekolah $record): string
    {
        if ($record->is_valid) {
            return 'Valid';
        }

        if ($record->relationLoaded('unggahan') ? $record->unggahan->isEmpty() : $record->unggahan()->count() === 0) {
            return 'Belum Diupload';
        }

        return 'Pending';
    }

    public static function generateSekolah(array $data): void
    {
        $kabkotaValue = $data['kabkota'] instanceof KabKota
            ? $data['kabkota']->value
            : (string) $data['kabkota'];

        $scopeProvinsi = str_contains(strtolower($kabkotaValue), 'provinsi');

        $jenjang = $scopeProvinsi
            ? self::JENJANG_PROVINSI
            : self::JENJANG_KAB_KOTA;

        $rows = SurveyPpg::query()
            ->selectRaw("
                sekolah_npsn,
                MAX(sekolah_nama) as sekolah_nama,
                MAX(sekolah_jenjang) as sekolah_jenjang,
                MAX(sekolah_kota) as sekolah_kota,
                MAX(sekolah_propinsi) as sekolah_propinsi,
                COUNT(*) FILTER (WHERE potensi_status IS DISTINCT FROM 'Berminat') as jumlah_guru
            ")
            ->whereNotNull('sekolah_npsn')
            ->whereIn('sekolah_jenjang', $jenjang)
            ->when(! $scopeProvinsi, fn ($q) => $q->where('sekolah_kota', $data['kabkota']))
            ->groupBy('sekolah_npsn')
            ->havingRaw("COUNT(*) FILTER (WHERE potensi_status IS DISTINCT FROM 'Berminat') > 0")
            ->get();

        $payload = $rows->map(fn ($r) => [
            'sekolah_npsn' => $r->sekolah_npsn,
            'sekolah_nama' => $r->sekolah_nama,
            'sekolah_jenjang' => $r->sekolah_jenjang,
            'sekolah_kota' => $r->sekolah_kota,
            'sekolah_propinsi' => $r->sekolah_propinsi,
            'scope' => $scopeProvinsi ? 'provinsi' : 'kabkota',
            'jumlah_guru' => $r->jumlah_guru,
            'generated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        SptjmSekolah::query()->upsert(
            $payload,
            uniqueBy: ['sekolah_npsn'],
            update: ['sekolah_nama', 'sekolah_jenjang', 'sekolah_kota', 'sekolah_propinsi', 'jumlah_guru', 'updated_at'],
        );

        $npsnInResult = $rows->pluck('sekolah_npsn');

        $toDelete = SptjmSekolah::query()
            ->when($scopeProvinsi, fn ($q) => $q->whereIn('sekolah_jenjang', self::JENJANG_PROVINSI))
            ->unless($scopeProvinsi, fn ($q) => $q
                ->where('sekolah_kota', $kabkotaValue)
                ->whereIn('sekolah_jenjang', self::JENJANG_KAB_KOTA)
            )
            ->whereNotIn('sekolah_npsn', $npsnInResult)
            ->where('jumlah_guru', '>', 0)
            ->pluck('id');

        $deleted = 0;

        if ($toDelete->isNotEmpty()) {
            SptjmUnggahan::query()
                ->whereIn('sptjm_sekolah_id', $toDelete)
                ->delete();

            $deleted = SptjmSekolah::query()
                ->whereIn('id', $toDelete)
                ->delete();
        }

        Notification::make()
            ->title('Generate selesai: '.$rows->count().' sekolah diproses'.($deleted ? ', '.$deleted.' sekolah dihapus (semua guru Berminat)' : ''))
            ->success()
            ->send();
    }

    private static function deleteAction(): Action
    {
        return Action::make('deleteSptjm')
            ->label('Hapus Docs')
            ->color('danger')
            ->icon(Heroicon::Trash)
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalHeading('Hapus Dokumen SPTJM?')
            ->modalDescription('Semua file upload SPTJM untuk sekolah ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->visible(fn (SptjmSekolah $record): bool => $record->unggahan()->count() > 0)
            ->action(function (SptjmSekolah $record): void {
                $unggahan = $record->unggahan()->get();

                foreach ($unggahan as $u) {
                    Storage::disk($u->disk)->delete($u->file_path);
                }

                $record->unggahan()->delete();

                $record->update(['is_valid' => false]);

                Notification::make()
                    ->title('Semua dokumen SPTJM berhasil dihapus')
                    ->success()
                    ->send();
            });
    }

    private static function toggleHardcopyAction(): Action
    {
        return Action::make('toggle_hardcopy')
            ->icon(fn (SptjmSekolah $record): Heroicon => $record->has_hardcopy ? Heroicon::XCircle : Heroicon::CheckCircle)
            ->color('gray')
            ->tooltip(fn (SptjmSekolah $record): string => $record->has_hardcopy ? 'Tandai Hardcopy Belum Diterima' : 'Tandai Hardcopy Sudah Diterima')
            ->action(function (SptjmSekolah $record): void {
                $record->update(['has_hardcopy' => ! $record->has_hardcopy]);

                Notification::make()
                    ->title($record->has_hardcopy
                        ? "Hardcopy {$record->sekolah_nama} ditandai Ada"
                        : "Hardcopy {$record->sekolah_nama} ditandai Tidak Ada"
                    )
                    ->success()
                    ->send();
            });
    }
}
