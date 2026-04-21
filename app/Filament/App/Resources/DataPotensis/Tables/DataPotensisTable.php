<?php

namespace App\Filament\App\Resources\DataPotensis\Tables;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DataPotensisTable
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
                IconColumn::make('is_serdik')
                    ->label('Serdik')
                    ->boolean(),
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
                TernaryFilter::make('is_check')
                    ->label('Dicek')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum'),
                TernaryFilter::make('is_serdik')
                    ->label('Serdik')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum'),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
