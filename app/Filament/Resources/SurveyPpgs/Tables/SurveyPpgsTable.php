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
                TextColumn::make('no_ukg')
                    ->label('No UKG')
                    ->searchable(),
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
                    ->label('💾 Berminat')
                    ->boolean(),
                IconColumn::make('has_verval')
                    ->label('💾 Lolos Adm')
                    ->boolean(),
                IconColumn::make('is_guru_dapodik')
                    ->label('Guru Dapodik')
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
                TextColumn::make('verval_wkt_ajuan')
                    ->label('Waktu Ajuan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('verval_wkt_verval')
                    ->label('Waktu Verval')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('verval_is_peserta')
                    ->label('Peserta Verval')
                    ->boolean(),
                TextColumn::make('verval_kandidat_skor_total_final')
                    ->label('Skor Final')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('verval_kandidat_is_lulus')
                    ->label('Lulus')
                    ->boolean(),
                TextColumn::make('verval_kandidat_status_seleksi')
                    ->label('Status Seleksi')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('potensi_status')
                    ->label('Status Potensi')
                    ->options(PotensiStatus::class),
                SelectFilter::make('peserta_layak_daftar')
                    ->label('Layak Daftar')
                    ->options(LayakDaftar::class),
                SelectFilter::make('verval_status')
                    ->label('Status Verval')
                    ->options(VervalStatus::class),
                TernaryFilter::make('has_potensi')
                    ->label('💾 Potensi')
                    ->trueLabel('Ada')
                    ->falseLabel('Tidak ada'),
                TernaryFilter::make('has_peserta')
                    ->label('💾 Berminat')
                    ->trueLabel('Ada')
                    ->falseLabel('Tidak ada'),
                TernaryFilter::make('has_verval')
                    ->label('💾 Lolos Adm')
                    ->trueLabel('Lolos')
                    ->falseLabel('Belum'),
                TernaryFilter::make('is_guru_dapodik')
                    ->label('Guru Dapodik')
                    ->trueLabel('Ya')
                    ->falseLabel('Tidak'),
            ])
            ->defaultSort('nama')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
