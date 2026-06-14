<?php

namespace App\Filament\Resources\SurveyPpgs\Tables;

use App\Enums\LayakDaftar;
use App\Enums\PotensiStatus;
use App\Enums\VervalStatus;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SurveyPpgsTable
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
                TextColumn::make('peserta_nik')
                    ->label('NIK')
                    ->searchable(),
                TextColumn::make('sekolah_nama')
                    ->label('Sekolah')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sekolah_jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sekolah_kota')
                    ->label('Kab/Kota')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('has_potensi')
                    ->label('💾 Potensi')
                    ->boolean(),
                IconColumn::make('has_peserta')
                    ->label('💾 Isi Survey')
                    ->boolean(),
                IconColumn::make('has_verval')
                    ->label('💾 Lolos Adm')
                    ->boolean(),
                TextColumn::make('potensi_status')
                    ->label('Status Potensi')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('potensi_waktu')
                    ->label('Waktu Potensi')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('peserta_layak_daftar')
                    ->label('Layak Daftar')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('peserta_keberminatan_waktu')
                    ->label('Waktu Keberminatan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verval_status')
                    ->label('Status Verval')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable(),
            ])
            ->filters([
                TernaryFilter::make('has_potensi')
                    ->label('💾 Potensi')
                    ->trueLabel('Ada')
                    ->falseLabel('Tidak ada'),
                TernaryFilter::make('has_peserta')
                    ->label('💾 Isi Survey')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum'),
                TernaryFilter::make('has_verval')
                    ->label('💾 Lolos Adm')
                    ->trueLabel('Lolos')
                    ->falseLabel('Belum'),
                SelectFilter::make('potensi_status')
                    ->label('Survey')
                    ->options(PotensiStatus::class),
                SelectFilter::make('peserta_keberminatan_status')
                    ->label('Status Keberminatan')
                    ->options(PotensiStatus::class),
                SelectFilter::make('peserta_layak_daftar')
                    ->label('Layak Daftar')
                    ->options(LayakDaftar::class),
                SelectFilter::make('verval_status')
                    ->label('Status Verval')
                    ->options(VervalStatus::class),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
