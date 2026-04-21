<?php

namespace App\Filament\App\Resources\DataPotensis\Tables;

use App\Filament\App\Resources\DataPotensis\DataPotensiResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DataPotensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
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
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
