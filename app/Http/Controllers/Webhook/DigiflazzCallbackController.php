<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\Supplier\DigiflazzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DigiflazzCallbackController extends Controller
{
    /**
     * Terima webhook status transaksi dari Digiflazz.
     */
    public function __invoke(Request $request, DigiflazzService $digiflazz): JsonResponse
    {
        $isValid = $digiflazz->isValidWebhookSignature($request->getContent(), $request->header('X-Hub-Signature'));

        ApiLog::create([
            'provider' => 'digiflazz',
            'direction' => 'callback',
            'reference' => $request->input('data.ref_id'),
            'request' => $request->all(),
            'signature_valid' => $isValid,
        ]);

        if (! $isValid) {
            return response()->json(['success' => false], 403);
        }

        // TODO (minggu 3): dispatch job HandleSupplierCallback
        //  - data.status "Sukses" -> order Success + simpan serial_number (data.sn)
        //  - data.status "Gagal"  -> order Failed (refund saldo / manual refund)

        return response()->json(['success' => true]);
    }
}
