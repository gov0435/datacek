<?php

namespace App\Filament\Resources\Whitelists\Schemas;

use App\Enums\KabKota;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Whitelist')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): string => strtolower(trim((string) $state)))
                            ->unique(ignoreRecord: true),
                        TextInput::make('nama')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('instansi')
                            ->label('Instansi')
                            ->required()
                            ->maxLength(255),
                        Select::make('kabkota')
                            ->label('Kabupaten/Kota')
                            ->options(KabKota::class)
                            ->required(),
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                'member' => 'Member',
                                'kgtk' => 'KGTK',
                            ])
                            ->default('member')
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }
}
