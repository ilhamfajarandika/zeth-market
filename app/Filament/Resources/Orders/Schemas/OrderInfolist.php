<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice'),
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('-'),
                TextEntry::make('game.name')
                    ->label('Game'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('product_type')
                    ->badge(),
                TextEntry::make('product_name'),
                TextEntry::make('target_user_id')
                    ->placeholder('-'),
                TextEntry::make('target_zone_id')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('whatsapp')
                    ->placeholder('-'),
                TextEntry::make('price')
                    ->money('IDR'),
                TextEntry::make('fee')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('payment_method')
                    ->placeholder('-'),
                TextEntry::make('payment_reference')
                    ->placeholder('-'),
                TextEntry::make('payment_url')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('payment_code')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('expired_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('supplier_reference')
                    ->placeholder('-'),
                TextEntry::make('supplier_status')
                    ->placeholder('-'),
                TextEntry::make('supplier_message')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('serial_number')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('completed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
