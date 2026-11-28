<?php

namespace App\Http\Controllers;

use App\Http\Requests\NicknameCheckRequest;
use App\Models\Game;
use App\Services\Supplier\MeloService;
use Illuminate\Http\JsonResponse;

/**
 * Cek nickname pelanggan lewat Melo (disetujui PO 2026-11-27). Hanya username dan region yang dikembalikan;
 * data billing Melo tidak ikut. Hasil tidak ditemukan atau galat dikembalikan sebagai null, bukan error.
 */
class NicknameCheckController extends Controller
{
    public function __invoke(NicknameCheckRequest $request, Game $game, MeloService $melo): JsonResponse
    {
        abort_unless($game->is_active && filled($game->melo_game_code), 404);

        $payload = [
            'game_code' => $game->melo_game_code,
            'customer_target' => $request->string('target_user_id')->trim()->toString(),
        ];

        if ($game->requires_zone_id) {
            $payload['customer_target_zone'] = $request->string('target_zone_id')->trim()->toString();
        }

        $player = $melo->cachedNickname($payload);

        return response()->json([
            'username' => $player['username'] ?? null,
            'region' => $player['region'] ?? null,
        ]);
    }
}
