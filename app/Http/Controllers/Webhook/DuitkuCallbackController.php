<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\HandlePaymentCallback;
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

        HandlePaymentCallback::dispatch($request->except('signature'));

        return response()->json(['success' => true]);
    }
}
