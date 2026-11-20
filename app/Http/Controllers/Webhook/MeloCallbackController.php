<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\HandleSupplierCallback;
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

        HandleSupplierCallback::dispatch($request->all());

        return response()->json(['success' => true]);
    }
}
