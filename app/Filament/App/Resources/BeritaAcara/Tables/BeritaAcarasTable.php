<?php

namespace App\Filament\App\Resources\BeritaAcara\Tables;

use App\Filament\App\Resources\BeritaAcara\BeritaAcaraResource;
use App\Helpers\FileHelper;
use App\Models\BeritaAcaraSekolah;
use App\Models\SurveyPpg;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BeritaAcarasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => BeritaAcaraResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Berita Acara per Sekolah'))
            ->columns([
                TextColumn::make('sekolah_nama')
                    ->label('Sekolah')
                    ->description(fn (BeritaAcaraSekolah $record): string => 'NPSN: '.$record->sekolah_npsn)
                    ->searchable(['sekolah_nama', 'sekolah_npsn'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->searchable(),
                TextColumn::make('jumlah_guru')
                    ->label('Jml Guru')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('status_ba')
                    ->label('Status BA')
                    ->state(fn (BeritaAcaraSekolah $record): string => static::getStatusBa($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Valid' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('unggahanValid.file_name')
                    ->label('File')
                    ->default('-'),
                TextColumn::make('unggahanValid.updated_at')
                    ->label('Tgl Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('jumlah_versi')
                    ->label('Versi')
                    ->state(fn (BeritaAcaraSekolah $record): int => $record->unggahan()->count())
                    ->alignRight(),
            ])
            ->headerActions([])
            ->filters([])
            ->defaultSort('sekolah_nama')
            ->recordActions([
                static::detailAction(),
            ])
            ->toolbarActions([]);
    }

    private static function getStatusBa(BeritaAcaraSekolah $record): string
    {
        $valid = $record->relationLoaded('unggahanValid')
            ? $record->unggahanValid
            : $record->unggahanValid()->first();

        if ($valid !== null) {
            return 'Valid';
        }

        $hasAny = $record->relationLoaded('unggahan')
            ? $record->unggahan->isNotEmpty()
            : $record->unggahan()->count() > 0;

        if ($hasAny) {
            return 'Pending';
        }

        return 'Belum Diupload';
    }

    private static function detailAction(): Action
    {
        return Action::make('detail')
            ->label('Detail')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->slideOver()
            ->schema(fn (BeritaAcaraSekolah $record): array => [
                Section::make('Informasi Sekolah')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('sekolah_npsn')
                                    ->label('NPSN')
                                    ->state(fn (BeritaAcaraSekolah $record): string => $record->sekolah_npsn),
                                TextEntry::make('sekolah_jenjang')
                                    ->label('Jenjang')
                                    ->state(fn (BeritaAcaraSekolah $record): string => $record->sekolah_jenjang ?? '-'),
                                TextEntry::make('sekolah_nama')
                                    ->label('Nama Sekolah')
                                    ->state(fn (BeritaAcaraSekolah $record): string => $record->sekolah_nama ?? '-')
                                    ->columnSpanFull(),
                                TextEntry::make('sekolah_kota')
                                    ->label('Kabupaten/Kota')
                                    ->state(fn (BeritaAcaraSekolah $record): string => $record->sekolah_kota ?? '-'),
                                TextEntry::make('jumlah_guru')
                                    ->label('Jumlah Guru')
                                    ->state(fn (BeritaAcaraSekolah $record): string => (string) ($record->jumlah_guru ?? '-')),
                            ]),
                    ])
                    ->compact(),

                Section::make('Upload Berita Acara')
                    ->afterHeader(fn (BeritaAcaraSekolah $record): array => ($unggahan = $record->unggahanValid()->first()) ? [

                        Action::make('downloadLatest')
                            ->label('Unduh PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->url(Storage::disk($unggahan->disk)->temporaryUrl($unggahan->file_path, now()->addMinutes(60)))
                            ->openUrlInNewTab(),
                    ] : [])
                    ->schema([
                        FileUpload::make('file')
                            ->label('File Berita Acara (PDF)')
                            ->columnSpanFull()
                            ->disk('s3')
                            ->directory(fn (BeritaAcaraSekolah $record): string => 'ppg/berita-acara/'.$record->sekolah_npsn)
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf'])
                            ->validationMessages([
                                'mimes' => 'File harus berupa PDF.',
                                'max' => 'Ukuran file maksimal 25 MB.',
                            ])
                            ->maxSize(25000)
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => FileHelper::generateUniqueFileName($file->getClientOriginalName())
                            )
                            ->required(),

                        Textarea::make('catatan')
                            ->label('Catatan (opsional)')
                            ->rows(3),
                    ])
                    ->compact(),

                Section::make('Daftar Guru Non-Berminat')
                    ->schema([
                        RepeatableEntry::make('daftar_guru')
                            ->state(fn (BeritaAcaraSekolah $record): array => $record->sekolah_npsn
                                ? SurveyPpg::query()
                                    ->where('sekolah_npsn', $record->sekolah_npsn)
                                    ->where(function ($q) {
                                        $q->where('potensi_status', '!=', 'Berminat')
                                            ->orWhereNull('potensi_status');
                                    })
                                    ->select(['nama', 'no_hp', 'potensi_status', 'potensi_alasan'])
                                    ->get()
                                    ->toArray()
                                : [])
                            ->table([
                                TableColumn::make('Nama'),
                                TableColumn::make('No HP'),
                                TableColumn::make('Status'),
                                TableColumn::make('Alasan'),
                            ])
                            ->schema([
                                TextEntry::make('nama'),
                                TextEntry::make('no_hp'),
                                TextEntry::make('potensi_status'),
                                TextEntry::make('potensi_alasan'),
                            ]),
                    ])
                    ->compact(),

            ])
            ->action(function (array $data, BeritaAcaraSekolah $record): void {
                DB::transaction(function () use ($data, $record): void {
                    $record->unggahan()->update(['is_valid' => false]);

                    $record->unggahan()->create([
                        'disk' => 's3',
                        'file_path' => $data['file'],
                        'file_name' => basename($data['file']),
                        'file_mime' => 'application/pdf',
                        'file_size' => null,
                        'is_valid' => true,
                        'catatan' => $data['catatan'] ?? null,
                        'uploaded_by' => Auth::id(),
                    ]);
                });

                Notification::make()
                    ->title('Berita Acara berhasil diupload')
                    ->success()
                    ->send();
            });
    }
}
