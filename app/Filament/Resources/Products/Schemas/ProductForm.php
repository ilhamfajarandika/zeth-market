<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\MeloProduct;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('supplier_sku', null))
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('group'),
                Select::make('supplier')
                    ->options(['melo' => 'Melo'])
                    ->required(),
                Select::make('supplier_sku')
                    ->label('SKU supplier')
                    ->options(fn (Get $get, ?Product $record): array => self::meloSkuOptions($get, $record))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        $item = MeloProduct::where('game_id', $get('game_id'))->where('sku_code', $state)->first();
                        if ($item === null) {
                            return;
                        }

                        $set('base_price', $item->price);
                        $set('group', $item->type_name);
                        if (blank($get('name'))) {
                            $set('name', $item->name);
                        }
                    })
                    ->helperText('Dari tab "Item Melo" game. Kosong? Tautkan game ke brand Melo lalu jalankan "Sync item dari Melo".')
                    ->required()
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
                TextInput::make('strike_price')
                    ->label('Harga coret')
                    ->integer()
                    ->minValue(0)
                    ->prefix('Rp'),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }

    /**
     * Item Melo milik game terpilih ([sku_code => label]). SKU produk yang sedang diedit selalu ikut,
     * supaya produk yang SKU-nya sudah hilang dari katalog tetap bisa disimpan.
     *
     * @return array<string, string>
     */
    private static function meloSkuOptions(Get $get, ?Product $record): array
    {
        $items = MeloProduct::where('game_id', $get('game_id'))->orderBy('price')->get();

        // Harga terbaik = SKU aktif termurah di antara SKU dengan nama sama (hanya jika ada >1 SKU).
        $bestPrices = $items->where('status', 'active')
            ->groupBy(fn (MeloProduct $item): string => mb_strtolower(trim($item->name)))
            ->filter(fn ($group): bool => $group->count() > 1)
            ->map(fn ($group): int => $group->min('price'));

        $options = $items
            ->mapWithKeys(fn (MeloProduct $item): array => [
                $item->sku_code => collect([
                    $item->status === 'active' && $bestPrices->get(mb_strtolower(trim($item->name))) === $item->price ? '★ Harga terbaik' : null,
                    $item->name,
                    $item->type_name,
                    'Rp '.number_format($item->price, 0, ',', '.'),
                    $item->status === 'active' ? null : $item->status,
                ])->filter()->implode(' · ')." [{$item->sku_code}]",
            ])
            ->all();

        if (filled($record?->supplier_sku)) {
            $options += [$record->supplier_sku => $record->supplier_sku];
        }

        return $options;
    }
}
