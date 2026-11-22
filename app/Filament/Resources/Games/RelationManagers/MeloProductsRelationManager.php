<?php

namespace App\Filament\Resources\Games\RelationManagers;

use App\Services\Supplier\MeloCatalogService;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Katalog Melo hasil sync (read-only). Admin memilih item lalu "Jadikan produk".
 */
class MeloProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'meloProducts';

    protected static ?string $title = 'Item Melo';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withExists('product'))
            ->defaultSort('price')
            ->columns([
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable(),
                TextColumn::make('type_name')
                    ->label('Grup'),
                TextColumn::make('sku_code')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Modal')
                    ->money('IDR', locale: 'id', decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'gray'),
                IconColumn::make('product_exists')
                    ->label('Sudah jadi produk')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('unpicked')
                    ->label('Belum jadi produk')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('product'))
                    ->toggle(),
            ])
            ->toolbarActions([
                BulkAction::make('addProducts')
                    ->label('Jadikan produk')
                    ->icon(Heroicon::OutlinedPlus)
                    ->requiresConfirmation()
                    ->modalDescription('Produk dibuat nonaktif dengan harga jual 0. Atur harga lalu aktifkan di menu Produk.')
                    ->action(function (Collection $records): void {
                        $created = app(MeloCatalogService::class)->addProducts($records);

                        Notification::make()
                            ->title("{$created} produk ditambahkan")
                            ->body($created < $records->count() ? 'Item yang sudah jadi produk dilewati.' : null)
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
