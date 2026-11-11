<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\Payment\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TripayCallbackController extends Controller
{
    /**
     * Terima callback status pembayaran dari Tripay.
     */
    public function __invoke(Request $request, TripayService $tripay): JsonResponse
    {
        $isValid = $tripay->isValidCallbackSignature($request->getContent(), $request->header('X-Callback-Signature'));

        ApiLog::create([
            'provider' => 'tripay',
            'direction' => 'callback',
            'reference' => $request->input('merchant_ref'),
            'request' => $request->all(),
            'signature_valid' => $isValid,
        ]);

        if (! $isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // TODO (minggu 2): dispatch job HandlePaymentCallback
        //  - cari order by merchant_ref, abaikan jika status sudah final (idempotent)
        //  - PAID    -> status Paid, lalu: topup => kirim ke Digiflazz, account => AccountStockService::markSold
        //  - EXPIRED / FAILED -> status Expired/Failed + AccountStockService::release

        return response()->json(['success' => true]);
    }
}
