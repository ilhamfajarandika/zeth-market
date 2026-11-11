<?php

namespace App\Services\Supplier;

use App\Models\ApiLog;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi supplier Digiflazz: cek saldo, daftar harga, transaksi, verifikasi webhook.
 *
 * @see https://developer.digiflazz.com
 */
class DigiflazzService
{
    public const BASE_URL = 'https://api.digiflazz.com/v1';

    public function balance(): Response
    {
        return $this->client()->post('/cek-saldo', [
            'cmd' => 'deposit',
            'username' => config('services.digiflazz.username'),
            'sign' => $this->sign('depo'),
        ]);
    }

    /**
     * Daftar harga prepaid. Dipakai untuk sync harga modal (base_price) produk.
     */
    public function priceList(): Response
    {
        return $this->client()->post('/price-list', [
            'cmd' => 'prepaid',
            'username' => config('services.digiflazz.username'),
            'sign' => $this->sign('pricelist'),
        ]);
    }

    /**
     * Kirim order top up ke supplier. ref_id = invoice (unik, dipakai juga untuk cek status).
     */
    public function topup(Order $order): Response
    {
        $customerNo = $order->target_user_id.($order->target_zone_id ?? '');

        $payload = [
            'username' => config('services.digiflazz.username'),
            'buyer_sku_code' => $order->product->supplier_sku,
            'customer_no' => $customerNo,
            'ref_id' => $order->invoice,
            'sign' => $this->sign($order->invoice),
            'testing' => (bool) config('services.digiflazz.testing'),
        ];

        $response = $this->client()->post('/transaction', $payload);

        ApiLog::create([
            'provider' => 'digiflazz',
            'direction' => 'outgoing',
            'reference' => $order->invoice,
            'endpoint' => '/transaction',
            'request' => collect($payload)->except('sign')->all(),
            'response' => $response->json(),
            'http_status' => $response->status(),
        ]);

        return $response;
    }

    /**
     * sign = md5(username + api_key + suffix).
     */
    public function sign(string $suffix): string
    {
        return md5(config('services.digiflazz.username').config('services.digiflazz.api_key').$suffix);
    }

    /**
     * Webhook Digiflazz mengirim header X-Hub-Signature = "sha1=" + HMAC-SHA1(raw body, secret).
     */
    public function isValidWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $expected = 'sha1='.hash_hmac('sha1', $rawBody, (string) config('services.digiflazz.webhook_secret'));

        return hash_equals($expected, $signature);
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
