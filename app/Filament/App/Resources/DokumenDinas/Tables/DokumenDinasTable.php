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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DokumenDinasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->heading(fn (): string => DokumenDinasResource::getWhitelistKabKotaHeading())
            ->description(new HtmlString('Dokumen Dinas'))
            ->columns([
                TextColumn::make('jenis')
                    ->label('Jenis Dokumen')
                    ->badge()
                    ->sortable()
                    ->description(fn (DokumenDinas $record): ?string => $record->catatan),
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
            ])
            ->headerActions([
                static::uploadDokumenHeaderAction(),
            ])
            ->filters([])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                static::unduhDokumenAction(),
                static::deleteDokumenAction(),
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
            ->url(fn (DokumenDinas $record): string => Storage::disk($record->disk)
                ->temporaryUrl($record->file_path, now()->addMinutes(60))
            )
            ->openUrlInNewTab();
    }

    private static function deleteDokumenAction(): Action
    {
        return Action::make('deleteDokumen')
            ->label('Hapus')
            ->color('danger')
            ->icon(Heroicon::Trash)
            ->modalIcon(Heroicon::OutlinedTrash)
            ->modalHeading('Hapus Dokumen Dinas?')
            ->modalDescription('Dokumen ini akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->modalCancelActionLabel('Batal')
            ->requiresConfirmation()
            ->action(function (DokumenDinas $record): void {
                Storage::disk($record->disk)->delete($record->file_path);

                $record->delete();

                Notification::make()
                    ->title('Dokumen berhasil dihapus')
                    ->success()
                    ->send();
            });
    }
}
