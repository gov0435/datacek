<?php

namespace App\Filament\App\Resources\Sptjm\Tables;

use App\Filament\App\Resources\Sptjm\SptjmResource;
use App\Helpers\FileHelper;
use App\Models\SptjmSekolah;
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

class SptjmsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => SptjmResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('SPTJM per Sekolah'))
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
                TextColumn::make('jumlah_guru')
                    ->label('Jml Guru')
                    ->sortable()
                    ->alignRight(),
                TextColumn::make('status_sptjm')
                    ->label('Status SPTJM')
                    ->state(fn (SptjmSekolah $record): string => static::getStatusSptjm($record))
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
                    ->state(fn (SptjmSekolah $record): int => $record->unggahan()->count())
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

    private static function getStatusSptjm(SptjmSekolah $record): string
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
            ->schema(fn (SptjmSekolah $record): array => [
                Section::make('Informasi Sekolah')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('sekolah_npsn')
                                    ->label('NPSN')
                                    ->state(fn (SptjmSekolah $record): string => $record->sekolah_npsn),
                                TextEntry::make('sekolah_jenjang')
                                    ->label('Jenjang')
                                    ->state(fn (SptjmSekolah $record): string => $record->sekolah_jenjang ?? '-'),
                                TextEntry::make('sekolah_nama')
                                    ->label('Nama Sekolah')
                                    ->state(fn (SptjmSekolah $record): string => $record->sekolah_nama ?? '-')
                                    ->columnSpanFull(),
                                TextEntry::make('sekolah_kota')
                                    ->label('Kabupaten/Kota')
                                    ->state(fn (SptjmSekolah $record): string => $record->sekolah_kota ?? '-'),
                                TextEntry::make('jumlah_guru')
                                    ->label('Jumlah Guru')
                                    ->state(fn (SptjmSekolah $record): string => (string) ($record->jumlah_guru ?? '-')),
                            ]),
                    ])
                    ->compact(),

                Section::make('Upload SPTJM')
                    ->afterHeader(fn (SptjmSekolah $record): array => ($unggahan = $record->unggahanValid()->first()) ? [

                        Action::make('downloadLatest')
                            ->label('Unduh PDF')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('primary')
                            ->url(Storage::disk($unggahan->disk)->temporaryUrl($unggahan->file_path, now()->addMinutes(60)))
                            ->openUrlInNewTab(),
                    ] : [])
                    ->schema([
                        FileUpload::make('file')
                            ->label('File SPTJM (PDF)')
                            ->columnSpanFull()
                            ->disk('s3')
                            ->directory(fn (SptjmSekolah $record): string => 'ppg/sptjm/'.$record->sekolah_npsn)
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
                            ->state(fn (SptjmSekolah $record): array => $record->sekolah_npsn
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
            ->action(function (array $data, SptjmSekolah $record): void {
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
                    ->title('SPTJM berhasil diupload')
                    ->success()
                    ->send();
            });
    }
}
