<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\Supplier\MeloService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeloCallbackController extends Controller
{
    /**
     * Terima webhook status transaksi dari Melo.
     */
    public function __invoke(Request $request, MeloService $melo): JsonResponse
    {
        $isValid = $melo->isValidWebhookSignature($request->getContent(), $request->header('X-H2H-Signature'));

        ApiLog::create([
            'provider' => 'melo',
            'direction' => 'callback',
            'reference' => $request->input('buyer_trx_id'),
            'request' => $request->all(),
            'signature_valid' => $isValid,
        ]);

        if (! $isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        // TODO (minggu 3): dispatch job HandleSupplierCallback. Callback yang sama bisa datang berkali-kali.
        //  - event "transaction.action_change" (aksi pelanggan/2FA): hanya simpan, JANGAN selesaikan order,
        //    ubah saldo, atau refund. Dedup dengan event_id + action.version.
        //  - tanpa event, status "success" -> order Success + simpan serial_number
        //  - tanpa event, status "failed"  -> order Failed (saldo yang ditahan dikembalikan oleh Melo)
        //  - abaikan jika OrderStatus::isFinal(); jawab 2xx setelah event tersimpan

        return response()->json(['success' => true]);
    }
}
