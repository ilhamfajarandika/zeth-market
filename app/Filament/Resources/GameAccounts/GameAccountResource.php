<?php

namespace App\Filament\Resources\GameAccounts;

use App\Filament\Resources\GameAccounts\Pages\CreateGameAccount;
use App\Filament\Resources\GameAccounts\Pages\EditGameAccount;
use App\Filament\Resources\GameAccounts\Pages\ListGameAccounts;
use App\Filament\Resources\GameAccounts\Schemas\GameAccountForm;
use App\Filament\Resources\GameAccounts\Tables\GameAccountsTable;
use App\Models\GameAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GameAccountResource extends Resource
{
    protected static ?string $model = GameAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'Stok Akun';

    protected static ?string $pluralModelLabel = 'Stok Akun';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return GameAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameAccounts::route('/'),
            'create' => CreateGameAccount::route('/create'),
            'edit' => EditGameAccount::route('/{record}/edit'),
        ];
    }
}
