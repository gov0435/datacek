<?php

namespace App\Filament\App\Resources\SeleksiAdmins\Tables;

use App\Filament\App\Resources\SeleksiAdmins\SeleksiAdminResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeleksiAdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => SeleksiAdminResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Data guru yang <b>lolos seleksi administrasi</b> Periode 1'))
            ->columns([
                TextColumn::make('ptk_id')
                    ->label('SIMPKB ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_hp')
                    ->label('No Telepon')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('No telepon disalin'),
                TextColumn::make('nuptk')
                    ->label('NUPTK')
                    ->searchable(),
                TextColumn::make('sekolah_nama')
                    ->label('Sekolah')
                    ->searchable(),
                TextColumn::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->searchable(),
                TextColumn::make('sekolah_kota')
                    ->label('Kota')
                    ->searchable(),
                TextColumn::make('verval_status')
                    ->label('Status Seleksi')
                    ->badge(),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->action(fn (): StreamedResponse => static::exportCsvResponse()),
            ])
            ->filters([
                SelectFilter::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->multiple()
                    ->options(static::getJenjangFilterOptions()),
                SelectFilter::make('sekolah_kota')
                    ->label('Kota')
                    ->options(SeleksiAdminResource::getKabKotaFilterOptions())
                    ->visible(fn (): bool => ! SeleksiAdminResource::isProvinsiScope()),
                TernaryFilter::make('verval_kandidat_is_lulus')
                    ->label('Lulus')
                    ->trueLabel('Lulus')
                    ->falseLabel('Tidak'),
                SelectFilter::make('verval_kandidat_status_seleksi')
                    ->label('Status Seleksi')
                    ->searchable(),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getJenjangFilterOptions(): array
    {
        $allowed = SeleksiAdminResource::getAllowedJenjangValues();

        return collect($allowed)
            ->mapWithKeys(fn (string $jenjang): array => [$jenjang => $jenjang])
            ->all();
    }

    public static function exportCsvResponse(): StreamedResponse
    {
        $fileName = 'seleksi-admin-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['SIMPKB ID', 'Nama', 'No Telepon', 'NUPTK', 'Sekolah', 'Jenjang', 'Kota', 'Status Seleksi', 'Skor Final', 'Lulus', 'Ket. Status']);

            SeleksiAdminResource::getEloquentQuery()
                ->select(['ptk_id', 'nama', 'no_hp', 'nuptk', 'sekolah_nama', 'sekolah_jenjang', 'sekolah_kota', 'verval_status', 'verval_kandidat_skor_total_final', 'verval_kandidat_is_lulus', 'verval_kandidat_status_seleksi'])
                ->orderBy('nama')
                ->cursor()
                ->each(function ($record) use ($handle): void {
                    fputcsv($handle, [
                        $record->ptk_id,
                        $record->nama,
                        $record->no_hp,
                        $record->nuptk,
                        $record->sekolah_nama,
                        $record->sekolah_jenjang,
                        $record->sekolah_kota,
                        $record->verval_status?->value ?? $record->verval_status,
                        $record->verval_kandidat_skor_total_final,
                        $record->verval_kandidat_is_lulus ? 'Ya' : 'Tidak',
                        $record->verval_kandidat_status_seleksi,
                    ]);
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
