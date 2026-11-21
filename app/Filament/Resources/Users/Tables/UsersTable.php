<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('orders'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('whatsapp')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('orders_count')
                    ->label('Pesanan')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())
                        ->reject(fn (UserRole $role): bool => $role === UserRole::Admin)
                        ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->getLabel()])
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
