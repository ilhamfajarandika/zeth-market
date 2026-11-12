<?php

namespace App\Services\Supplier;

use App\Models\ApiLog;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Integrasi supplier Melo (H2H). Satu method per endpoint di dokumentasi resmi.
 *
 * Autentikasi memakai header X-API-Key + X-Secret-Key (tanpa signature request). Dua endpoint
 * `check-nickname/games` dan `check-nickname/plan` bersifat publik. Semua method mengembalikan
 * Response mentah; pemanggil yang membaca `data.status` dan menangani 4xx/5xx.
 *
 * Katalog (reguler, Smart, kategori) berbagi 20 request/menit. Jika 429, tunggu header Retry-After.
 *
 * @see https://h2h.melostore.id/id/docs
 */
class MeloService
{
    /**
     * Jumlah item per halaman katalog reguler yang direkomendasikan Melo untuk sinkronisasi.
     */
    public const PRICELIST_SYNC_LIMIT = 500;

    // ---------------------------------------------------------------------
    // Akun
    // ---------------------------------------------------------------------

    /**
     * Cek akses akun dan tier. Tidak ada endpoint autentikasi terpisah.
     */
    public function profile(): Response
    {
        return $this->client()->get('/profile');
    }

    /**
     * Saldo H2H dan kurs.
     */
    public function balance(): Response
    {
        return $this->client()->get('/profile/balance');
    }

    // ---------------------------------------------------------------------
    // Katalog
    // ---------------------------------------------------------------------

    /**
     * Katalog reguler. Dipakai untuk sync harga modal (base_price) produk. Ikuti meta.pagination.next_cursor.
     *
     * @param  array{limit?: int, cursor?: string, server?: string, format?: 'compact'|'legacy', brand_id?: int}  $query  limit 1-1000
     */
    public function priceList(array $query = []): Response
    {
        return $this->client()->get('/pricelists', ['limit' => self::PRICELIST_SYNC_LIMIT, ...$query]);
    }

    /**
     * Katalog reguler per brand.
     *
     * @param  array{limit?: int, cursor?: string, server?: string, format?: 'compact'|'legacy', brand_id?: int}  $query
     */
    public function priceListByBrand(string $categorySlug, array $query = []): Response
    {
        return $this->client()->get('/pricelists/'.rawurlencode($categorySlug), ['limit' => self::PRICELIST_SYNC_LIMIT, ...$query]);
    }

    /**
     * Daftar brand katalog reguler.
     */
    public function brands(): Response
    {
        return $this->client()->get('/pricelists/categories');
    }

    /**
     * Katalog Smart (SKU virtual dengan banyak varian; harga ditentukan saat order).
     *
     * @param  array{limit?: int, cursor?: string, search?: string, brand_id?: int, brand?: int|string, category?: int|string}  $query  `brand` dan `category` sudah deprecated
     */
    public function smartPriceList(array $query = []): Response
    {
        return $this->client()->get('/smart-pricelists', $query);
    }

    /**
     * Katalog Smart per brand.
     *
     * @param  array{limit?: int, cursor?: string, search?: string, brand_id?: int, brand?: int|string, category?: int|string}  $query
     */
    public function smartPriceListByBrand(string $categorySlug, array $query = []): Response
    {
        return $this->client()->get('/smart-pricelists/'.rawurlencode($categorySlug), $query);
    }

    /**
     * Daftar brand katalog Smart.
     */
    public function smartBrands(): Response
    {
        return $this->client()->get('/smart-pricelists/categories');
    }

    // ---------------------------------------------------------------------
    // Transaksi
    // ---------------------------------------------------------------------

    /**
     * Kirim order top up (SKU reguler) ke supplier. buyer_trx_id = invoice: unik dan dipakai ulang saat
     * retry (tanpa debit ganda) maupun untuk cek status.
     *
     * Respons 201 berarti diterima, bukan selesai. Baca data.status dan tunggu webhook.
     * Respons 500 berarti hasil tidak diketahui: cek status() sebelum mengulang.
     */
    public function topup(Order $order): Response
    {
        return $this->createTransaction([
            'sku_code' => $order->product->supplier_sku,
            'customer_target' => $order->target_user_id,
            'customer_target_zone' => $order->target_zone_id,
            'buyer_trx_id' => $order->invoice,
        ]);
    }

    /**
     * Buat order reguler. 200 = buyer_trx_id sudah ada (tanpa debit ganda), 201 = diterima, 422 = validasi
     * atau MAX_PRICE_TOO_LOW.
     *
     * sandbox_mode diisi dari config('services.melo.sandbox') jika tidak diberikan.
     *
     * @param  array{sku_code: string, customer_target: string, buyer_trx_id: string, customer_target_zone?: ?string, additional_data?: ?array<string, mixed>, sandbox_mode?: ?bool, max_price?: ?int}  $payload  buyer_trx_id maks 64, max_price harus >= harga tier (saldo ditahan penuh)
     */
    public function createTransaction(array $payload): Response
    {
        $payload = $this->withSandboxMode($payload);

        $response = $this->client()->post('/transaction', $payload);

        $this->logOutgoing($payload['buyer_trx_id'], '/transaction', $payload, $response);

        return $response;
    }

    /**
     * Buat order Smart. sku_code = smart_sku_code dari katalog Smart. max_bid ditahan penuh di muka dan
     * sisanya dikembalikan setelah harga akhir diketahui. SKU hasil bisa berbeda dari yang dipesan:
     * cocokkan dengan id atau buyer_trx_id.
     *
     * @param  array{sku_code: string, customer_target: string, buyer_trx_id: string, max_bid: int, customer_target_zone?: ?string, additional_data?: ?array<string, mixed>, sandbox_mode?: ?bool}  $payload
     */
    public function createSmartTransaction(array $payload): Response
    {
        $payload = $this->withSandboxMode($payload);

        $response = $this->client()->post('/smart-transaction', $payload);

        $this->logOutgoing($payload['buyer_trx_id'], '/smart-transaction', $payload, $response);

        return $response;
    }

    /**
     * Cek status transaksi. $reference boleh ID transaksi Melo atau buyer_trx_id (invoice).
     */
    public function status(string $reference): Response
    {
        $response = $this->client()->get('/transaction/'.rawurlencode($reference));

        $this->logOutgoing($reference, '/transaction/{id}', [], $response);

        return $response;
    }

    /**
     * Kirim kode 2FA yang diminta untuk transaksi (reguler atau Smart). 202 berarti diterima, bukan valid.
     * Batas 5 request/menit per transaksi. Kode yang sama boleh dikirim ulang jika pengiriman tidak pasti.
     * Endpoint ini tidak membuat order dan tidak mengubah status transaksi atau keuangan.
     *
     * @param  array{buyer_trx_id: string, code: string, action_id?: string, action_version?: int}  $payload  isi kolom action dari webhook transaction.action_change
     */
    public function submitTwoFactor(array $payload): Response
    {
        $response = $this->client()->post('/transactions/2fa', $payload);

        $this->logOutgoing($payload['buyer_trx_id'], '/transactions/2fa', ['buyer_trx_id' => $payload['buyer_trx_id']], $response);

        return $response;
    }

    // ---------------------------------------------------------------------
    // Bulk order
    // ---------------------------------------------------------------------

    /**
     * Buat bulk order: order_count (1-100) order dengan SKU reguler yang sama, diproses berurutan.
     * Request identik dengan buyer_bulk_id yang sama mengembalikan bulk awal; payload berbeda = 409.
     * Callback dikirim per transaksi (tidak ada callback untuk bulk secara keseluruhan).
     *
     * @param  array{sku_code: string, customer_target: string, buyer_bulk_id: string, order_count: int, customer_target_zone?: ?string, additional_data?: ?array<string, mixed>, sandbox_mode?: ?bool}  $payload
     */
    public function createBulkOrder(array $payload): Response
    {
        $payload = $this->withSandboxMode($payload);

        $response = $this->client()->post('/bulk-orders', $payload);

        $this->logOutgoing($payload['buyer_bulk_id'], '/bulk-orders', $payload, $response);

        return $response;
    }

    /**
     * Riwayat bulk order (berpaginasi).
     *
     * @param  array{page?: int, status?: 'queued'|'processing'|'completed'|'partially_failed'|'failed', search?: string, start_date?: string, end_date?: string}  $query
     */
    public function bulkOrders(array $query = []): Response
    {
        return $this->client()->get('/bulk-orders', $query);
    }

    /**
     * Progres satu bulk order.
     */
    public function bulkOrder(string $id): Response
    {
        return $this->client()->get('/bulk-orders/'.rawurlencode($id));
    }

    /**
     * Transaksi anak dari satu bulk order (berpaginasi).
     */
    public function bulkTransactions(string $id, ?int $page = null): Response
    {
        return $this->client()->get('/bulk-orders/'.rawurlencode($id).'/transactions', array_filter(['page' => $page]));
    }

    /**
     * Invoice PDF bulk yang sudah selesai (isi respons = bytes PDF). 409 jika belum tersedia atau bulk sandbox.
     */
    public function bulkInvoice(string $id): Response
    {
        return $this->client()->accept('application/pdf')->get('/bulk-orders/'.rawurlencode($id).'/invoice');
    }

    // ---------------------------------------------------------------------
    // Cek akun (di luar scope produk saat ini: spec proyek tidak memakai cek nickname)
    // ---------------------------------------------------------------------

    /**
     * Daftar game yang mendukung cek akun (game_code, zone_required, zone_values). Publik, tanpa kredensial.
     */
    public function nicknameGames(): Response
    {
        return $this->publicClient()->get('/check-nickname/games');
    }

    /**
     * Kuota dan tarif cek akun saat ini. Publik, tanpa kredensial.
     */
    public function nicknamePlan(): Response
    {
        return $this->publicClient()->get('/check-nickname/plan');
    }

    /**
     * Cek nickname akun. Billing mengikuti mode akun (tidak ada sandbox per request); hasil ditemukan
     * maupun tidak ditemukan memakai kuota atau biaya, termasuk pengecekan berulang.
     *
     * @param  array{game_code: string, customer_target: string, customer_target_zone?: ?string}  $payload  game_code dari nicknameGames()
     */
    public function checkNickname(array $payload): Response
    {
        $response = $this->client()->post('/check-nickname', $payload);

        $this->logOutgoing($payload['customer_target'], '/check-nickname', $payload, $response);

        return $response;
    }

    /**
     * Batas pembelian Mobile Legends per paket (flag per paket, bukan angka batas). Memakai kuota/billing
     * yang sama dengan cek nickname.
     */
    public function checkMobileLegendsPurchaseLimit(string $customerTarget, string $customerTargetZone): Response
    {
        $payload = ['customer_target' => $customerTarget, 'customer_target_zone' => $customerTargetZone];

        $response = $this->client()->post('/mobile-legends/purchase-limit', $payload);

        $this->logOutgoing($customerTarget, '/mobile-legends/purchase-limit', $payload, $response);

        return $response;
    }

    // ---------------------------------------------------------------------
    // Webhook
    // ---------------------------------------------------------------------

    /**
     * Webhook Melo mengirim header X-H2H-Signature = hex HMAC-SHA256(raw body, webhook secret).
     * Melo mencoba maksimal 3 kali (jeda 30 dan 120 detik) dan menunggu respons 2xx dalam 10 detik.
     */
    public function isValidWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secret = (string) config('services.melo.webhook_secret');

        if ($signature === null || $signature === '' || $secret === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), strtolower($signature));
    }

    // ---------------------------------------------------------------------
    // Internal
    // ---------------------------------------------------------------------

    protected function publicClient(): PendingRequest
    {
        return Http::baseUrl((string) config('services.melo.base_url'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    protected function client(): PendingRequest
    {
        return $this->publicClient()->withHeaders([
            'X-API-Key' => (string) config('services.melo.api_key'),
            'X-Secret-Key' => (string) config('services.melo.secret_key'),
        ]);
    }

    /**
     * Buang field null dan isi sandbox_mode dari config jika pemanggil tidak menentukannya.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withSandboxMode(array $payload): array
    {
        $payload += ['sandbox_mode' => (bool) config('services.melo.sandbox')];

        return array_filter($payload, fn (mixed $value): bool => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function logOutgoing(string $reference, string $endpoint, array $payload, Response $response): void
    {
        ApiLog::create([
            'provider' => 'melo',
            'direction' => 'outgoing',
            'reference' => $reference,
            'endpoint' => $endpoint,
            'request' => $payload,
            'response' => $response->json(),
            'http_status' => $response->status(),
        ]);
    }
}
