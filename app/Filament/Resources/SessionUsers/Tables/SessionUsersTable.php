<?php

namespace App\Filament\Resources\SessionUsers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
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
