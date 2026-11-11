<?php

namespace App\Services\Payment;

use App\Models\ApiLog;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Tripay (closed payment): buat transaksi & verifikasi callback.
 *
 * @see https://tripay.co.id/developer
 */
class TripayService
{
    public function baseUrl(): string
    {
        return config('services.tripay.sandbox')
            ? 'https://tripay.co.id/api-sandbox'
            : 'https://tripay.co.id/api';
    }

    /**
     * Daftar channel pembayaran aktif (QRIS, VA, e-wallet, ...).
     */
    public function paymentChannels(): Response
    {
        return $this->client()->get('/merchant/payment-channel');
    }

    /**
     * Buat transaksi pembayaran untuk sebuah order.
     */
    public function createTransaction(Order $order, string $method): Response
    {
        $amount = (int) $order->total;

        $payload = [
            'method' => $method,
            'merchant_ref' => $order->invoice,
            'amount' => $amount,
            'customer_name' => $order->email ?? 'Customer',
            'customer_email' => $order->email ?? 'noreply@example.com',
            'customer_phone' => $order->whatsapp,
            'order_items' => [[
                'name' => $order->product_name,
                'price' => $amount,
                'quantity' => 1,
            ]],
            'return_url' => route('invoice.show', $order),
            'expired_time' => $order->expired_at?->timestamp ?? now()->addMinutes(15)->timestamp,
            'signature' => $this->transactionSignature($order->invoice, $amount),
        ];

        $response = $this->client()->post('/transaction/create', $payload);

        ApiLog::create([
            'provider' => 'tripay',
            'direction' => 'outgoing',
            'reference' => $order->invoice,
            'endpoint' => '/transaction/create',
            'request' => $payload,
            'response' => $response->json(),
            'http_status' => $response->status(),
        ]);

        return $response;
    }

    public function transactionSignature(string $merchantRef, int $amount): string
    {
        return hash_hmac(
            'sha256',
            config('services.tripay.merchant_code').$merchantRef.$amount,
            (string) config('services.tripay.private_key'),
        );
    }

    /**
     * Callback Tripay mengirim header X-Callback-Signature = HMAC-SHA256(raw body, private key).
     */
    public function isValidCallbackSignature(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, (string) config('services.tripay.private_key'));

        return hash_equals($expected, $signature);
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.tripay.api_key'))
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 500, throw: false);
    }
}
