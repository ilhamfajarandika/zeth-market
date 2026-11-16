<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
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
            ]);
    }
}
