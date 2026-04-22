<?php

namespace App\Filament\App\Resources\DataPotensis\Tables;

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
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->searchable()
                    ->url(fn ($record): ?string => filled($record->npsn) ? "https://referensi.data.kemendikdasmen.go.id/pendidikan/npsn/{$record->npsn}" : null)
                    ->openUrlInNewTab(),
                TextColumn::make('sta_asn')
                    ->label('Status ASN')
                    ->badge(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
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
                    ->options(StatusPPG::class),
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

            fputcsv($handle, ['SIMPKB ID', 'Nama', 'NPSN', 'Status ASN', 'Jenjang', 'Kota', 'Status PPG']);

            DataPotensiResource::getEloquentQuery()
                ->select(['ptk_id', 'nama', 'npsn', 'sta_asn', 'jenjang', 'kota', 'statusppg'])
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
                    ]);
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
