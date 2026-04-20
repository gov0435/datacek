<?php

namespace App\Filament\Resources\SessionUsers\Tables;

use App\Enums\KabKota;
use App\Models\SessionUser;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class SessionUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Session ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('user.kabkota')
                    ->label('Kab/Kota')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestamp($state)->diffForHumans())
                    ->sortable(),
            ])
            ->filters([])
            ->defaultSort('last_activity', 'desc')
            ->recordActions([
                Action::make('updateKabkota')
                    ->label('Update Kab/Kota')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Select::make('kabkota')
                            ->label('Kabupaten/Kota')
                            ->options(KabKota::class)
                            ->required(),
                    ])
                    ->fillForm(fn (SessionUser $record): array => [
                        'kabkota' => $record->user?->kabkota,
                    ])
                    ->action(function (array $data, SessionUser $record): void {
                        if ($record->user) {
                            $record->user->update([
                                'kabkota' => $data['kabkota'],
                            ]);
                        }
                    })
                    ->visible(fn (SessionUser $record): bool => $record->user !== null),
                DeleteAction::make()
                    ->label('Hapus Session')
                    ->modalHeading('Hapus session aktif?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus session terpilih'),
                ]),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('user'));
    }
}
