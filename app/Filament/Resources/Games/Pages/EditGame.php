<?php

namespace App\Filament\Resources\Games\Pages;

use App\Filament\Resources\Games\Actions\SyncMeloProductsAction;
use App\Filament\Resources\Games\GameResource;
use App\Models\Game;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            SyncMeloProductsAction::make(),
            // orders.game_id = ON DELETE RESTRICT: game yang pernah dipesan tidak boleh dihapus (DATABASE_DESIGN.md).
            DeleteAction::make()
                ->disabled(fn (Game $record): bool => $record->orders()->exists())
                ->tooltip(fn (Game $record): ?string => $record->orders()->exists()
                    ? 'Game sudah punya pesanan sehingga tidak bisa dihapus. Nonaktifkan lewat toggle "Aktif".'
                    : null),
        ];
    }
}
