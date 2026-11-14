<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Services\Payment\DuitkuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DuitkuCallbackController extends Controller
{
    /**
     * Terima callback status pembayaran dari Duitku (form-urlencoded).
     */
    public function __invoke(Request $request, DuitkuService $duitku): JsonResponse
    {
        $isValid = $duitku->isValidCallbackSignature($request->all());

        ApiLog::create([
            'provider' => 'duitku',
            'direction' => 'callback',
            'reference' => $request->input('merchantOrderId'),
            'request' => $request->except('signature'),
            'signature_valid' => $isValid,
        ]);

        if (! $isValid) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        // TODO (minggu 2): dispatch job HandlePaymentCallback
        //  - cari order by merchantOrderId, abaikan jika status sudah final (idempotent)
        //  - cocokkan `amount` callback dengan orders.total
        //  - resultCode 00 -> status Paid, lalu: topup => kirim ke Melo, account => AccountStockService::markSold
        //  - resultCode 01 -> Failed + AccountStockService::release
        //  - kedaluwarsa tidak dikirim lewat callback: ditandai dari expired_at / transactionStatus()

        return response()->json(['success' => true]);
    }
}
