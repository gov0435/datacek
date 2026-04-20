<?php

namespace App\Filament\Resources\PotensiPpgs\Tables;

use App\Enums\Jenjang;
use App\Enums\KabKota;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PotensiPpgsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('sta_asn')
                    ->label('Status ASN')
                    ->badge(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->badge(),
                TextColumn::make('kota')
                    ->label('Kota')
                    ->searchable(),
                TextColumn::make('status_ajuan')
                    ->label('Status Ajuan')
                    ->badge(),
                TextColumn::make('status_daftar')
                    ->label('Status Daftar')
                    ->badge(),
                IconColumn::make('is_check')
                    ->label('Dicek')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->multiple()
                    ->options(Jenjang::class),
                SelectFilter::make('kota')
                    ->label('Kota')
                    ->options(KabKota::class),
                TernaryFilter::make('is_check')
                    ->label('Dicek')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum'),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([])
            ->modifyQueryUsing(fn ($query) => $query->whereNotNull('ptk_id'));
    }
}
