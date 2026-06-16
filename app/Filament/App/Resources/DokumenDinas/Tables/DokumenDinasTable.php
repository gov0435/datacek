<?php

namespace App\Filament\App\Resources\DokumenDinas\Tables;

use App\Enums\JenisDokumenDinas;
use App\Filament\App\Resources\DokumenDinas\DokumenDinasResource;
use App\Helpers\FileHelper;
use App\Models\DokumenDinas;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DokumenDinasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => DokumenDinasResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Dokumen Dinas per Jenis'))
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status_dokumen')
                    ->label('Status')
                    ->state(fn (DokumenDinas $record): string => static::getStatusLabel($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Valid' => 'success',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_valid')
                    ->label('Valid')
                    ->visible(fn (): bool => Auth::user()?->isKgtk())
                    ->afterStateUpdated(function (DokumenDinas $record, bool $state): void {
                        Notification::make()
                            ->title($state
                                ? "Dokumen {$record->jenis->getLabel()} ditandai Valid"
                                : "Dokumen {$record->jenis->getLabel()} ditandai Tidak Valid"
                            )
                            ->success()
                            ->send();
                    }),
                TextColumn::make('file_name')
                    ->label('File')
                    ->default('-')
                    ->formatStateUsing(fn (?string $state): ?string => FileHelper::trimFileName($state))
                    ->tooltip(fn (DokumenDinas $record): ?string => $record->file_name),
                TextColumn::make('updated_at')
                    ->label('Tgl Upload')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('jumlah_versi')
                    ->label('Versi')
                    ->state(fn (DokumenDinas $record): int => $record->id
                        ? DokumenDinas::where('kabkota', $record->kabkota)
                            ->where('jenis', $record->jenis)
                            ->count()
                        : 0
                    )
                    ->alignRight(),
            ])
            ->headerActions([
                static::uploadDokumenHeaderAction(),
            ])
            ->filters([])
            ->defaultSort('jenis')
            ->recordActions([
                static::unduhDokumenAction(),
            ])
            ->toolbarActions([]);
    }

    private static function getStatusLabel(DokumenDinas $record): string
    {
        if ($record->id === null) {
            return 'Belum Diupload';
        }

        return $record->is_valid ? 'Valid' : 'Pending';
    }

    private static function uploadDokumenHeaderAction(): Action
    {
        return Action::make('uploadDokumen')
            ->label('Upload Dokumen Dinas')
            ->icon('heroicon-o-arrow-up-tray')
            ->modalHeading('Upload Dokumen Dinas')
            ->modalWidth('lg')
            ->form([
                Select::make('jenis')
                    ->label('Jenis Dokumen')
                    ->options(JenisDokumenDinas::class)
                    ->required(),
                FileUpload::make('file')
                    ->label('File Dokumen (PDF)')
                    ->columnSpanFull()
                    ->disk('s3')
                    ->directory('ppg/dokumen-dinas')
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
            ->action(function (array $data): void {
                $kabkota = DokumenDinasResource::getAuthenticatedWhitelistKabKota();

                // Extract enum value if needed
                $jenis = $data['jenis'];
                if ($jenis instanceof JenisDokumenDinas) {
                    $jenis = $jenis->value;
                }

                DB::transaction(function () use ($data, $kabkota, $jenis): void {
                    DokumenDinas::where('kabkota', $kabkota)
                        ->where('jenis', $jenis)
                        ->update(['is_valid' => false]);

                    DokumenDinas::create([
                        'kabkota' => $kabkota,
                        'jenis' => $jenis,
                        'disk' => 's3',
                        'file_path' => $data['file'],
                        'file_name' => basename($data['file']),
                        'file_mime' => 'application/pdf',
                        'file_size' => null,
                        'is_valid' => false,
                        'catatan' => $data['catatan'] ?? null,
                        'uploaded_by' => Auth::id(),
                    ]);
                });

                Notification::make()
                    ->title('Dokumen berhasil diupload')
                    ->success()
                    ->send();
            });
    }

    private static function unduhDokumenAction(): Action
    {
        return Action::make('unduhDokumen')
            ->label('Unduh')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (DokumenDinas $record): ?StreamedResponse {
                $valid = DokumenDinas::where('kabkota', $record->kabkota)
                    ->where('jenis', $record->jenis)
                    ->where('is_valid', true)
                    ->first();

                if ($valid === null) {
                    Notification::make()
                        ->title('Belum ada dokumen yang valid')
                        ->warning()
                        ->send();

                    return null;
                }

                return static::downloadFile($valid);
            });
    }

    private static function downloadFile(DokumenDinas $record): StreamedResponse
    {
        return Storage::disk($record->disk)->download($record->file_path, $record->file_name);
    }
}
