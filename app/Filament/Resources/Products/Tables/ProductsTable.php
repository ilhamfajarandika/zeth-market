<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('game')->withStock())
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('game.name')
                    ->label('Game')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('group')
                    ->toggleable(),
                TextColumn::make('available_accounts_count')
                    ->label('Stok akun')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'danger',
                        $state <= 3 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (int $state, Product $record): string => $record->isAccount() ? (string) $state : '—')
                    ->sortable(),
                TextColumn::make('supplier_sku')
                    ->label('SKU')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('base_price')
                    ->label('Modal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('reseller_price')
                    ->label('Harga reseller')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ProductType::class),
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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
