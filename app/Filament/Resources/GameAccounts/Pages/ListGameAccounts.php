<?php

namespace App\Filament\Resources\GameAccounts\Pages;

use App\Filament\Resources\GameAccounts\GameAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameAccounts extends ListRecords
{
    protected static string $resource = GameAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
