<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

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
                    ->default(ProductType::Topup)
                    ->live()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('group'),
                FileUpload::make('image')
                    ->image(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Select::make('supplier')
                    ->options(['melo' => 'Melo'])
                    ->live()
                    ->required(fn (Get $get): bool => self::isTopup($get))
                    ->visible(fn (Get $get): bool => self::isTopup($get)),
                TextInput::make('supplier_sku')
                    ->label('SKU supplier')
                    ->required(fn (Get $get): bool => self::isTopup($get))
                    ->visible(fn (Get $get): bool => self::isTopup($get))
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('supplier', $get('supplier')),
                    ),
                TextInput::make('base_price')
                    ->label('Harga modal')
                    ->helperText('Item Melo diperbarui otomatis saat sync.')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true)
                    ->prefix('Rp'),
                TextInput::make('price')
                    ->label('Harga jual')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->live(onBlur: true)
                    ->prefix('Rp')
                    ->helperText(fn (Get $get): ?string => filled($get('price')) && (int) $get('price') < (int) $get('base_price')
                        ? 'Peringatan: harga jual di bawah harga modal.'
                        : null),
                TextInput::make('reseller_price')
                    ->integer()
                    ->minValue(0)
                    ->prefix('Rp'),
                TextInput::make('strike_price')
                    ->label('Harga coret')
                    ->integer()
                    ->minValue(0)
                    ->prefix('Rp'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                TextInput::make('sort')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    private static function isTopup(Get $get): bool
    {
        $type = $get('type');

        return ($type instanceof ProductType ? $type : ProductType::tryFrom((string) $type)) === ProductType::Topup;
    }
}
