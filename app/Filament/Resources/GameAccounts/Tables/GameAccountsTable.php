<?php

namespace App\Filament\Resources\GameAccounts\Tables;

use App\Enums\AccountStatus;
use App\Enums\ProductType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GameAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.game', 'order']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('product.game.name')
                    ->label('Game')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('public_info')
                    ->label('Info publik')
                    ->limit(40),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('order.invoice')
                    ->label('Invoice')
                    ->searchable(),
                TextColumn::make('reserved_until')
                    ->label('Ditahan sampai')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sold_at')
                    ->label('Terjual')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AccountStatus::class),
                SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name', fn (Builder $query) => $query->where('type', ProductType::Account))
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
