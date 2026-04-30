<?php

namespace App\Filament\App\Resources\DataPotensis\Tables;

use App\Enums\LayakDaftar;
use App\Enums\StatusDaftar;
use App\Enums\StatusPPG;
use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPotensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => DataPotensiResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Data Guru yang mendapatkan notifikasi <b><a href="https://ppg.kemendikdasmen.go.id/news/penjaringan-data-guru-tertentu-belum-berserdik" target="_blank" rel="noopener noreferrer" class="text-shadow-blue-900">survey penjaringan PPG Tahun 2026</a></b>'))
            ->columns([
                TextColumn::make('ptk_id')
                    ->label('SIMPKB ID')
                    ->description(fn ($record): ?string => filled($record->npsn) ? "NPSN: {$record->npsn}" : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->description(fn ($record): ?string => filled($record->nik) ? "NIK: {$record->nik}" : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sta_asn')
                    ->label('Status ASN')
                    ->badge(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->badge(),
                TextColumn::make('layak_daftar')
                    ->label('Kelayakan')
                    ->badge(),
                TextColumn::make('status_daftar')
                    ->label('Status Daftar')
                    ->badge(),
                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable(),
                SelectColumn::make('statusppg')
                    ->label('Check Dinas')
                    ->options(StatusPPG::class)
                    ->afterStateUpdated(function ($record, $state) {
                        $statusLabel = $record->statusppg instanceof StatusPPG
                            ? $record->statusppg->getLabel()
                            : $state;

                        Notification::make()
                            ->title('Status berhasil diperbarui')
                            ->body("Status PPG untuk {$record->nama} diubah menjadi {$statusLabel}")
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->action(fn (): StreamedResponse => static::exportCsvResponse()),
            ])
            ->filters([
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->multiple()
                    ->options(DataPotensiResource::getJenjangFilterOptions()),
                SelectFilter::make('kota')
                    ->label('Kota')
                    ->options(DataPotensiResource::getKabKotaFilterOptions())
                    ->visible(fn (): bool => ! DataPotensiResource::isProvinsiScope()),
                SelectFilter::make('statusppg')
                    ->label('Status PPG')
                    ->options([
                        'potensi' => 'Potensi',
                        ...collect(StatusPPG::cases())->mapWithKeys(fn (StatusPPG $status): array => [$status->value => $status->getLabel()])->all(),
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        if ($value === 'potensi') {
                            return $query->whereNull('statusppg');
                        }

                        return $query->where('statusppg', $value);
                    }),
                SelectFilter::make('layak_daftar')
                    ->label('Kelayakan')
                    ->options(LayakDaftar::class),
                SelectFilter::make('status_daftar')
                    ->label('Status Daftar')
                    ->options(StatusDaftar::class),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function exportCsvResponse(): StreamedResponse
    {
        $fileName = 'data-potensi-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, ['SIMPKB ID', 'Nama', 'NPSN', 'Status ASN', 'Jenjang', 'Kota', 'Status PPG', 'Layak Daftar', 'Status Daftar']);

            DataPotensiResource::getEloquentQuery()
                ->select(['ptk_id', 'nama', 'npsn', 'sta_asn', 'jenjang', 'kota', 'statusppg', 'layak_daftar', 'status_daftar'])
                ->orderBy('nama')
                ->cursor()
                ->each(function ($record) use ($handle): void {
                    fputcsv($handle, [
                        $record->ptk_id,
                        $record->nama,
                        $record->npsn,
                        $record->sta_asn,
                        $record->jenjang?->value ?? $record->jenjang,
                        $record->kota,
                        $record->statusppg?->getLabel() ?? $record->statusppg,
                        $record->layak_daftar?->value ?? $record->layak_daftar,
                        $record->status_daftar?->value ?? $record->status_daftar,
                    ]);
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
