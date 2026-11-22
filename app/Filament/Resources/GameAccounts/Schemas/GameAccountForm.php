<?php

namespace App\Filament\Resources\GameAccounts\Schemas;

use App\Enums\AccountStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GameAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('game_id')
                    ->label('Game')
                    ->relationship('game', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label('Nama akun')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga jual')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->prefix('Rp'),
                Select::make('status')
                    ->helperText('Akun dijual manual di luar sistem: ubah ke Terjual setelah akun diserahkan ke pembeli.')
                    ->options(AccountStatus::class)
                    ->default(AccountStatus::Available->value)
                    ->required(),
                Textarea::make('public_info')
                    ->label('Info publik')
                    ->helperText('Ditampilkan di halaman game, contoh: rank, jumlah skin, level.')
                    ->columnSpanFull(),
                DateTimePicker::make('sold_at')
                    ->label('Terjual pada')
                    ->disabled(),
            ]);
    }
}
