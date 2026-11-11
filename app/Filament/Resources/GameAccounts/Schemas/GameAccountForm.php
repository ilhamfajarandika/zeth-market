<?php

namespace App\Filament\Resources\GameAccounts\Schemas;

use App\Enums\AccountStatus;
use App\Enums\ProductType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class GameAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produk akun')
                    ->relationship(
                        'product',
                        'name',
                        fn (Builder $query) => $query->where('type', ProductType::Account),
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->options(AccountStatus::class)
                    ->default(AccountStatus::Available)
                    ->required(),
                Textarea::make('credentials')
                    ->label('Data login (rahasia)')
                    ->helperText('Disimpan terenkripsi. Hanya ditampilkan ke pembeli setelah pembayaran sukses.')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('public_info')
                    ->label('Info publik')
                    ->helperText('Ditampilkan di halaman produk, contoh: rank, jumlah skin, level.')
                    ->columnSpanFull(),
                Select::make('order_id')
                    ->label('Order')
                    ->relationship('order', 'invoice')
                    ->disabled(),
                DateTimePicker::make('reserved_until')
                    ->label('Ditahan sampai')
                    ->disabled(),
                DateTimePicker::make('sold_at')
                    ->label('Terjual pada')
                    ->disabled(),
            ]);
    }
}
