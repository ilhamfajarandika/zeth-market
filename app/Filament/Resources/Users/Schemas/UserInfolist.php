<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama'),
                TextEntry::make('email'),
                TextEntry::make('whatsapp')
                    ->placeholder('-'),
                TextEntry::make('role')
                    ->badge(),
                TextEntry::make('orders_count')
                    ->label('Jumlah pesanan')
                    ->state(fn (User $record): int => $record->orders()->count()),
                TextEntry::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime(),
                RepeatableEntry::make('orders')
                    ->label('Riwayat pesanan')
                    ->placeholder('Belum ada pesanan')
                    ->columns(6)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('invoice'),
                        TextEntry::make('product_name')
                            ->label('Produk'),
                        TextEntry::make('payment_method')
                            ->label('Metode bayar')
                            ->placeholder('-'),
                        TextEntry::make('fee')
                            ->label('Potongan (fee)')
                            ->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('total')
                            ->money('IDR', locale: 'id', decimalPlaces: 0),
                        TextEntry::make('status')
                            ->badge(),
                    ]),
            ]);
    }
}
