<?php

namespace App\Filament\Resources\GameAccounts\Pages;

use App\Filament\Resources\GameAccounts\GameAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGameAccount extends EditRecord
{
    protected static string $resource = GameAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
