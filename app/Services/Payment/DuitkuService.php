<?php

namespace App\Services\Payment;

use App\Models\ApiLog;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi Duitku (API langsung): daftar metode bayar, buat transaksi,
 * cek status, dan verifikasi callback. Tanpa library pihak ketiga.
 *
 * @see https://docs.duitku.com
 */
class DuitkuService
{
    public function baseUrl(): string
    {
        return config('services.duitku.sandbox')
            ? 'https://sandbox.duitku.com/webapi/api/merchant'
            : 'https://passport.duitku.com/webapi/api/merchant';
    }

    /**
     * Daftar metode pembayaran aktif untuk nominal tertentu (VA, QRIS, e-wallet, ...).
     */
    public function paymentMethods(int $amount): Response
    {
        $datetime = now()->format('Y-m-d H:i:s');

        $payload = [
            'merchantcode' => $this->merchantCode(),
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => hash_hmac('sha256', $this->merchantCode().$amount.$datetime, $this->apiKey()),
        ];

        return $this->send('/paymentmethod/getpaymentmethod', $payload, reference: null);
    }

    /**
     * Buat transaksi pembayaran untuk sebuah order. Nominal selalu dari `orders.total`.
     */
    public function createTransaction(Order $order, string $method): Response
    {
        $amount = (int) $order->total;
        $email = $order->email ?? 'noreply@example.com';

        $payload = [
            'merchantCode' => $this->merchantCode(),
            'paymentAmount' => $amount,
            'paymentMethod' => $method,
            'merchantOrderId' => $order->invoice,
            'productDetails' => $order->product_name,
            'additionalParam' => '',
            'merchantUserInfo' => '',
            'customerVaName' => 'Customer',
            'email' => $email,
            'phoneNumber' => $order->whatsapp,
            'itemDetails' => [[
                'name' => $order->product_name,
                'price' => $amount,
                'quantity' => 1,
            ]],
            'callbackUrl' => route('webhook.duitku'),
            'returnUrl' => route('invoice.show', $order),
            'signature' => $this->transactionSignature($order->invoice, $amount),
            'expiryPeriod' => $this->expiryPeriodInMinutes($order),
        ];

        return $this->send('/v2/inquiry', $payload, $order->invoice);
    }

    /**
     * Cek status transaksi berdasarkan invoice (`statusCode`: 00 sukses, 01 pending, 02 gagal/kedaluwarsa).
     */
    public function transactionStatus(string $invoice): Response
    {
        $payload = [
            'merchantCode' => $this->merchantCode(),
            'merchantOrderId' => $invoice,
            'signature' => md5($this->merchantCode().$invoice.$this->apiKey()),
        ];

        return $this->send('/transactionStatus', $payload, $invoice, asForm: true);
    }

    public function transactionSignature(string $merchantOrderId, int $amount): string
    {
        return hash_hmac($this->merchantCode().$merchantOrderId.$amount, $this->apiKey());
    }

    /**
     * Callback Duitku (form-urlencoded) membawa field `signature` =
     * MD5(merchantCode + amount + merchantOrderId + apiKey). Ditolak jika API key
     * belum dikonfigurasi atau merchantCode callback bukan milik kita.
     *
     * @param  array<string, mixed>  $payload
     */
    public function isValidCallbackSignature(array $payload): bool
    {
        $signature = $payload['signature'] ?? null;

        if (! is_string($signature) || $signature === '' || $this->apiKey() === '' || $this->merchantCode() === '') {
            return false;
        }

        $merchantCode = (string) ($payload['merchantCode'] ?? '');

        if (! hash_equals($this->merchantCode(), $merchantCode)) {
            return false;
        }

        $expected = md5($merchantCode.($payload['amount'] ?? '').($payload['merchantOrderId'] ?? '').$this->apiKey());

        return hash_equals($expected, strtolower($signature));
    }

    protected function expiryPeriodInMinutes(Order $order): int
    {
        if ($order->expired_at === null) {
            return 15;
        }

        return max(1, (int) ceil(now()->diffInMinutes($order->expired_at, absolute: false)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function send(string $endpoint, array $payload, ?string $reference, bool $asForm = false): Response
    {
        $client = $this->client();

        if ($asForm) {
            $client = $client->asForm();
        }

        $response = $client->post($endpoint, $payload);

        ApiLog::create([
            'provider' => 'duitku',
            'direction' => 'outgoing',
            'reference' => $reference,
            'endpoint' => $endpoint,
            'request' => $this->withoutSignature($payload),
            'response' => $response->json(),
            'http_status' => $response->status(),
        ]);

        return $response;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withoutSignature(array $payload): array
    {
        unset($payload['signature']);

        return $payload;
    }

    protected function merchantCode(): string
    {
        return (string) config('services.duitku.merchant_code');
    }

    protected function apiKey(): string
    {
        return (string) config('services.duitku.api_key');
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 500, throw: false);
    }
}
