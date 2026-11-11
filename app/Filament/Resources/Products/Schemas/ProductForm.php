<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('game_id')
                    ->relationship('game', 'name')
                    ->required(),
                Select::make('type')
                    ->options(ProductType::class)
                    ->default('topup')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('group'),
                FileUpload::make('image')
                    ->image(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('supplier'),
                TextInput::make('supplier_sku'),
                TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),
                TextInput::make('reseller_price')
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('strike_price')
                    ->numeric()
                    ->prefix('Rp'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
