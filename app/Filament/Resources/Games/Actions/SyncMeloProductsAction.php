<?php

namespace App\Filament\Resources\Games\Actions;

use App\Models\Game;
use App\Services\Supplier\MeloCatalogService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Throwable;

class SyncMeloProductsAction
{
    public static function make(): Action
    {
        return Action::make('syncMeloProducts')
            ->label('Sync item dari Melo')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('info')
            ->visible(fn (Game $record): bool => $record->melo_brand_id !== null)
            ->requiresConfirmation()
            ->modalDescription('Katalog Melo disimpan di tab Item Melo untuk dipilih. Produk yang sudah dipilih hanya diperbarui nama, grup, dan harga modal; yang habis dinonaktifkan.')
            ->action(function (Game $record): void {
                try {
                    $result = app(MeloCatalogService::class)->syncProducts($record);
                } catch (Throwable $e) {
                    report($e);

                    Notification::make()
                        ->title('Sync Melo gagal')
                        ->body('Tidak ada data yang diubah. '.$e->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Sync Melo selesai')
                    ->body("Item katalog: {$result['catalog']}, produk diperbarui: {$result['updated']}, dinonaktifkan: {$result['deactivated']}.")
                    ->success()
                    ->send();
            });
    }
}
