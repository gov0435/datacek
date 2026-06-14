<?php

namespace App\Filament\App\Resources\DataKeberminatan\Tables;

use App\Enums\LayakDaftar;
use App\Enums\PotensiStatus;
use App\Filament\App\Resources\DataKeberminatan\DataKeberminatanResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataKeberminatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => DataKeberminatanResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Data Guru berdasarkan survey <b>keberminatan PPG</b>'))
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
                TextColumn::make('potensi_status')
                    ->label('Status Keberminatan')
                    ->badge(),
                TextColumn::make('peserta_layak_daftar')
                    ->label('Layak Daftar')
                    ->badge(),
                IconColumn::make('has_peserta')
                    ->label('Terdaftar')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('has_verval')
                    ->label('Seleksi Adm P1')
                    ->boolean(),
                TextColumn::make('verval_status')
                    ->label('Ket. Seleksi Adm'),
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
                SelectFilter::make('potensi_status')
                    ->label('Keberminatan')
                    ->multiple()
                    ->options(PotensiStatus::class),
                SelectFilter::make('peserta_layak_daftar')
                    ->label('Layak Daftar')
                    ->options(LayakDaftar::class),
                TernaryFilter::make('has_peserta')
                    ->label('Konfirm Berminat')
                    ->trueLabel('Sudah Konfirm')
                    ->falseLabel('Belum'),
                TernaryFilter::make('has_verval')
                    ->label('Seleksi Adm')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getJenjangFilterOptions(): array
    {
        $allowed = DataKeberminatanResource::getAllowedJenjangValues();

        return collect($allowed)
            ->mapWithKeys(fn (string $jenjang): array => [$jenjang => $jenjang])
            ->all();
    }

    public static function exportCsvResponse(): StreamedResponse
    {
        $fileName = 'data-keberminatan-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['SIMPKB ID', 'Nama', 'No Telepon', 'Sekolah', 'Jenjang', 'Kota', 'Status Potensi', 'Status Keberminatan', 'Layak Daftar', 'Terdaftar', 'Verval']);

            DataKeberminatanResource::getEloquentQuery()
                ->select(['ptk_id', 'nama', 'no_hp', 'sekolah_nama', 'sekolah_jenjang', 'sekolah_kota', 'potensi_status', 'peserta_keberminatan_status', 'peserta_layak_daftar', 'has_peserta', 'has_verval'])
                ->orderBy('nama')
                ->cursor()
                ->each(function ($record) use ($handle): void {
                    fputcsv($handle, [
                        $record->ptk_id,
                        $record->nama,
                        $record->no_hp,
                        $record->sekolah_nama,
                        $record->sekolah_jenjang,
                        $record->sekolah_kota,
                        $record->potensi_status?->value ?? $record->potensi_status,
                        $record->peserta_keberminatan_status,
                        $record->peserta_layak_daftar?->value ?? $record->peserta_layak_daftar,
                        $record->has_peserta ? 'Ya' : 'Tidak',
                        $record->has_verval ? 'Ya' : 'Tidak',
                    ]);
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
