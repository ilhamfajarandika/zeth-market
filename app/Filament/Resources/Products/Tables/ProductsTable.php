<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('game'))
            ->defaultSort('price')
            ->columns([
                TextColumn::make('game.name')
                    ->label('Game')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('group')
                    ->toggleable(),
                TextColumn::make('supplier_sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('base_price')
                    ->label('Modal')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->sortable(),
                TextInputColumn::make('price')
                    ->label('Harga jual')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:0'])
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active_only')
                    ->label('Aktif saja')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),
                Filter::make('unpriced')
                    ->label('Belum ada harga jual')
                    ->query(fn (Builder $query): Builder => $query->where('price', 0))
                    ->toggle(),
                SelectFilter::make('game_id')
                    ->label('Game')
                    ->relationship('game', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::setPriceBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Isi harga jual produk terpilih dari harga modal. Alat bantu sekali jalan, bukan aturan markup tersimpan.
     */
    private static function setPriceBulkAction(): BulkAction
    {
        return BulkAction::make('setPrice')
            ->label('Atur harga jual')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->schema([
                Select::make('mode')
                    ->label('Jenis markup')
                    ->options(['percent' => 'Persen dari harga modal', 'fixed' => 'Rupiah tetap di atas harga modal'])
                    ->default('percent')
                    ->required(),
                TextInput::make('amount')
                    ->label('Nilai markup')
                    ->helperText('Persen (mis. 5) atau Rupiah (mis. 1000), sesuai jenis markup.')
                    ->required()
                    ->integer()
                    ->minValue(0),
                Select::make('round_to')
                    ->label('Bulatkan ke atas ke kelipatan')
                    ->options([1 => 'Tanpa pembulatan', 100 => 'Rp 100', 500 => 'Rp 500', 1000 => 'Rp 1.000'])
                    ->default(100)
                    ->required(),
                Toggle::make('only_unpriced')
                    ->label('Hanya produk yang belum punya harga jual')
                    ->default(true),
            ])
            ->action(function (Collection $records, array $data): void {
                $targets = $records->when(
                    $data['only_unpriced'],
                    fn (Collection $products) => $products->filter(fn (Product $product): bool => $product->price === 0),
                );

                foreach ($targets as $product) {
                    $product->update(['price' => Product::priceFromBase(
                        $product->base_price,
                        $data['mode'],
                        (int) $data['amount'],
                        (int) $data['round_to'],
                    )]);
                }

                Notification::make()
                    ->title("Harga jual diatur untuk {$targets->count()} produk")
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
