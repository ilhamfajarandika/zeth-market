<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('game_id')
                    ->relationship('game', 'name')
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('product_name')
                    ->required(),
                TextInput::make('target_user_id'),
                TextInput::make('target_zone_id'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('whatsapp'),
                TextInput::make('price')
                    ->required()
                    ->integer()->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('fee')
                    ->required()
                    ->integer()->minValue(0)
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->integer()->minValue(0),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('pending')
                    ->required(),
                TextInput::make('payment_method'),
                TextInput::make('payment_reference'),
                Textarea::make('payment_url')
                    ->columnSpanFull(),
                Textarea::make('payment_code')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('expired_at'),
                TextInput::make('supplier_reference'),
                TextInput::make('supplier_status'),
                Textarea::make('supplier_message')
                    ->columnSpanFull(),
                Textarea::make('serial_number')
                    ->columnSpanFull(),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
