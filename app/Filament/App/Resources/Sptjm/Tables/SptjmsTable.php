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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
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
                ToggleColumn::make('is_valid')
                    ->label('Valid')
                    ->visible(fn (): bool => Auth::user()?->isKgtk())
                    ->afterStateUpdated(function (SptjmSekolah $record, bool $state): void {
                        Notification::make()
                            ->title($state
                                ? "SPTJM {$record->sekolah_nama} ditandai Valid"
                                : "SPTJM {$record->sekolah_nama} ditandai Tidak Valid"
                            )
                            ->success()
                            ->send();
                    }),
                IconColumn::make('has_hardcopy')
                    ->label('Hardcopy')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('unggahanValid.file_name')
                    ->label('File')
                    ->default('-')
                    ->formatStateUsing(fn (?string $state): ?string => FileHelper::trimFileName($state))
                    ->tooltip(fn (SptjmSekolah $record): ?string => $record->unggahanValid?->file_name),
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
            ->filters([
                SelectFilter::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->multiple()
                    ->options(SptjmResource::getJenjangFilterOptions()),
                SelectFilter::make('status_sptjm')
                    ->label('Status SPTJM')
                    ->options([
                        'valid' => 'Valid',
                        'pending' => 'Pending',
                        'belum_diupload' => 'Belum Diupload',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return match ($value) {
                            'valid' => $query->where('is_valid', true),
                            'pending' => $query->where('is_valid', false)->whereHas('unggahan'),
                            'belum_diupload' => $query->where('is_valid', false)->whereDoesntHave('unggahan'),
                            default => $query,
                        };
                    }),
                SelectFilter::make('has_hardcopy')
                    ->label('Status Hardcopy')
                    ->options([
                        '1' => 'Hardcopy Ada',
                        '0' => 'Hardcopy Tidak Ada',
                    ])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        return $query->where('has_hardcopy', filter_var($value, FILTER_VALIDATE_BOOLEAN));
                    }),
            ])
            ->defaultSort('sekolah_nama')
            ->recordActions([
                static::detailAction(),
                Action::make('toggle_hardcopy')
                    ->label('Hardcopy')
                    ->icon(fn (SptjmSekolah $record): Heroicon => $record->has_hardcopy ? Heroicon::CheckCircle : Heroicon::XCircle)
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
                    })
                    ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin', 'kgtk'], true)),
            ])
            ->toolbarActions([]);
    }

    private static function getStatusSptjm(SptjmSekolah $record): string
    {
        if ($record->is_valid) {
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
            ->modalSubmitAction(
                fn (Action $action): Action => $action
                    ->label('Upload')
            )
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
                            ->required(fn (SptjmSekolah $record): bool => ! ($record->is_valid && $record->unggahanValid()->exists()))
                            ->disabled(fn (SptjmSekolah $record): bool => $record->is_valid && $record->unggahanValid()->exists())
                            ->deletable(fn (SptjmSekolah $record): bool => ! ($record->is_valid && $record->unggahanValid()->exists()))
                            ->helperText(fn (SptjmSekolah $record): ?string => ($record->is_valid && $record->unggahanValid()->exists())
                                ? 'SPTJM sudah divalidasi dan tidak dapat diubah.'
                                : null),

                        Textarea::make('catatan')
                            ->label('Catatan (opsional)')
                            ->rows(3)
                            ->disabled(fn (SptjmSekolah $record): bool => $record->is_valid && $record->unggahanValid()->exists()),
                    ])
                    ->compact(),

                Section::make('Daftar Guru Tidak Berminat PPG, Sedang PPG, Sudah Serdik')
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
                if ($record->is_valid && $record->unggahanValid()->exists()) {
                    Notification::make()
                        ->title('SPTJM sudah divalidasi dan tidak dapat diubah.')
                        ->danger()
                        ->send();

                    return;
                }

                DB::transaction(function () use ($data, $record): void {
                    $record->unggahan()->create([
                        'disk' => 's3',
                        'file_path' => $data['file'],
                        'file_name' => basename($data['file']),
                        'file_mime' => 'application/pdf',
                        'file_size' => null,
                        'catatan' => $data['catatan'] ?? null,
                        'uploaded_by' => Auth::id(),
                    ]);

                    if (Auth::user()?->isKgtk()) {
                        $record->update(['has_hardcopy' => true]);
                    } else {
                        $record->update(['has_hardcopy' => false]);
                    }
                });

                Notification::make()
                    ->title('SPTJM berhasil diupload')
                    ->success()
                    ->send();
            });
    }
}
