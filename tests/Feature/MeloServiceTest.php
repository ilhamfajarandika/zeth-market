<?php

namespace Tests\Feature;

use App\Models\ApiLog;
use App\Models\Order;
use App\Models\Product;
use App\Services\Supplier\MeloService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MeloServiceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://melo.test/api/v1/h2h';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => self::BASE_URL,
            'services.melo.api_key' => 'test-api-key',
            'services.melo.secret_key' => 'test-secret-key',
            'services.melo.webhook_secret' => 'test-webhook-secret',
            'services.melo.sandbox' => true,
        ]);
    }

    public function test_topup_sends_authenticated_request_with_invoice_as_buyer_trx_id_and_logs_it(): void
    {
        Http::fake([
            self::BASE_URL.'/transaction' => Http::response([
                'success' => true,
                'data' => ['id' => 'trx-1', 'buyer_trx_id' => 'INV1', 'status' => 'pending'],
            ], 201),
        ]);

        $product = Product::factory()->create(['supplier_sku' => 'ML-86']);
        $order = Order::factory()->for($product)->create([
            'invoice' => 'INV1',
            'target_user_id' => '12345678',
            'target_zone_id' => '2001',
        ]);

        $response = app(MeloService::class)->topup($order);

        $this->assertSame(201, $response->status());
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/transaction'
            && $request->hasHeader('X-API-Key', 'test-api-key')
            && $request->hasHeader('X-Secret-Key', 'test-secret-key')
            && $request->data() === [
                'sku_code' => 'ML-86',
                'customer_target' => '12345678',
                'customer_target_zone' => '2001',
                'buyer_trx_id' => 'INV1',
                'sandbox_mode' => true,
            ]);

        $log = ApiLog::where('provider', 'melo')->sole();
        $this->assertSame('INV1', $log->reference);
        $this->assertSame(201, $log->http_status);
        $this->assertArrayNotHasKey('X-Secret-Key', $log->request);
    }

    public function test_topup_omits_zone_when_order_has_none(): void
    {
        Http::fake([self::BASE_URL.'/transaction' => Http::response(['success' => true], 201)]);

        $order = Order::factory()->create(['target_zone_id' => null]);

        app(MeloService::class)->topup($order);

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('customer_target_zone', $request->data()));
    }

    public function test_status_looks_up_transaction_by_reference(): void
    {
        Http::fake([
            self::BASE_URL.'/transaction/INV1' => Http::response([
                'success' => true,
                'data' => ['buyer_trx_id' => 'INV1', 'status' => 'success', 'serial_number' => 'SN-1'],
            ]),
        ]);

        $response = app(MeloService::class)->status('INV1');

        $this->assertSame('success', $response->json('data.status'));
        $this->assertSame('INV1', ApiLog::where('provider', 'melo')->sole()->reference);
    }

    public function test_profile_and_balance_use_credentials_headers(): void
    {
        Http::fake([
            self::BASE_URL.'/profile' => Http::response(['success' => true]),
            self::BASE_URL.'/profile/balance' => Http::response(['success' => true]),
        ]);

        $melo = app(MeloService::class);
        $melo->profile();
        $melo->balance();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-API-Key', 'test-api-key'));
    }

    public function test_price_list_requests_500_items_per_page_by_default(): void
    {
        Http::fake([self::BASE_URL.'/pricelists*' => Http::response(['data' => []])]);

        app(MeloService::class)->priceList();
        app(MeloService::class)->priceList(['limit' => 100, 'cursor' => 'abc']);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'limit=500'));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'limit=100')
            && str_contains($request->url(), 'cursor=abc'));
    }

    /**
     * @return array<string, array{0: string, 1: array<int, mixed>, 2: string, 3: string, 4: array<string, mixed>}>
     */
    public static function endpointProvider(): array
    {
        return [
            'profile' => ['profile', [], 'GET', '/profile', []],
            'balance' => ['balance', [], 'GET', '/profile/balance', []],
            'pricelists' => ['priceList', [['cursor' => 'c1', 'brand_id' => 7]], 'GET', '/pricelists', ['limit' => '500', 'cursor' => 'c1', 'brand_id' => '7']],
            'pricelists by brand' => ['priceListByBrand', ['mobile legends', ['format' => 'legacy']], 'GET', '/pricelists/mobile%20legends', ['limit' => '500', 'format' => 'legacy']],
            'brands' => ['brands', [], 'GET', '/pricelists/categories', []],
            'smart pricelists' => ['smartPriceList', [['search' => 'diamond', 'limit' => 50]], 'GET', '/smart-pricelists', ['search' => 'diamond', 'limit' => '50']],
            'smart pricelists by brand' => ['smartPriceListByBrand', ['free-fire', ['brand_id' => 3]], 'GET', '/smart-pricelists/free-fire', ['brand_id' => '3']],
            'smart brands' => ['smartBrands', [], 'GET', '/smart-pricelists/categories', []],
            'bulk history' => ['bulkOrders', [['status' => 'completed', 'page' => 2]], 'GET', '/bulk-orders', ['status' => 'completed', 'page' => '2']],
            'bulk progress' => ['bulkOrder', ['bulk-1'], 'GET', '/bulk-orders/bulk-1', []],
            'bulk children' => ['bulkTransactions', ['bulk-1', 3], 'GET', '/bulk-orders/bulk-1/transactions', ['page' => '3']],
            'bulk children without page' => ['bulkTransactions', ['bulk-1'], 'GET', '/bulk-orders/bulk-1/transactions', []],
            'bulk invoice' => ['bulkInvoice', ['bulk-1'], 'GET', '/bulk-orders/bulk-1/invoice', []],
        ];
    }

    /**
     * @param  array<int, mixed>  $arguments
     * @param  array<string, mixed>  $expectedQuery
     */
    #[DataProvider('endpointProvider')]
    public function test_each_read_endpoint_uses_expected_method_path_query_and_credentials(string $method, array $arguments, string $httpMethod, string $path, array $expectedQuery): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        app(MeloService::class)->{$method}(...$arguments);

        Http::assertSent(function (Request $request) use ($httpMethod, $path, $expectedQuery): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === $httpMethod
                && parse_url($request->url(), PHP_URL_PATH) === '/api/v1/h2h'.$path
                && $query === $expectedQuery
                && $request->hasHeader('X-API-Key', 'test-api-key')
                && $request->hasHeader('X-Secret-Key', 'test-secret-key');
        });
    }

    public function test_bulk_invoice_asks_for_pdf(): void
    {
        Http::fake(['*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);

        $response = app(MeloService::class)->bulkInvoice('bulk-1');

        $this->assertSame('%PDF-1.4', $response->body());
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Accept', 'application/pdf'));
    }

    public function test_nickname_games_and_plan_are_public_and_send_no_credentials(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $melo = app(MeloService::class);
        $melo->nicknameGames();
        $melo->nicknamePlan();

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => ! $request->hasHeader('X-API-Key') && ! $request->hasHeader('X-Secret-Key'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/check-nickname/games'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/check-nickname/plan'));
    }

    public function test_create_transaction_drops_null_fields_and_keeps_explicit_sandbox_mode(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 201)]);

        app(MeloService::class)->createTransaction([
            'sku_code' => 'ML-1',
            'customer_target' => '1',
            'buyer_trx_id' => 'INV9',
            'customer_target_zone' => null,
            'max_price' => 15000,
            'sandbox_mode' => false,
        ]);

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'sku_code' => 'ML-1',
            'customer_target' => '1',
            'buyer_trx_id' => 'INV9',
            'max_price' => 15000,
            'sandbox_mode' => false,
        ]);
    }

    public function test_smart_transaction_and_bulk_order_post_to_their_endpoints_with_default_sandbox_and_are_logged(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 201)]);

        $melo = app(MeloService::class);
        $melo->createSmartTransaction(['sku_code' => 'SMART-1', 'customer_target' => '1', 'buyer_trx_id' => 'INV10', 'max_bid' => 20000]);
        $melo->createBulkOrder(['sku_code' => 'ML-1', 'customer_target' => '1', 'buyer_bulk_id' => 'BULK1', 'order_count' => 3]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/smart-transaction')
            && $request->method() === 'POST'
            && $request['max_bid'] === 20000
            && $request['sandbox_mode'] === true);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/bulk-orders')
            && $request->method() === 'POST'
            && $request['order_count'] === 3
            && $request['sandbox_mode'] === true);
        $this->assertSame(['INV10', 'BULK1'], ApiLog::where('provider', 'melo')->orderBy('id')->pluck('reference')->all());
    }

    public function test_two_factor_code_is_sent_but_never_written_to_the_log(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 202)]);

        app(MeloService::class)->submitTwoFactor([
            'buyer_trx_id' => 'INV11',
            'code' => '123456',
            'action_id' => 'b6a9d6fa-4c3b-4a0e-9d9e-3a8f3d4b2c11',
            'action_version' => 2,
        ]);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/transactions/2fa') && $request['code'] === '123456');

        $log = ApiLog::where('provider', 'melo')->sole();
        $this->assertSame(['buyer_trx_id' => 'INV11'], $log->request);
    }

    public function test_nickname_and_purchase_limit_checks_post_to_their_endpoints(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);

        $melo = app(MeloService::class);
        $melo->checkNickname(['game_code' => 'mobile-legends', 'customer_target' => '12345', 'customer_target_zone' => '2001']);
        $melo->checkMobileLegendsPurchaseLimit('12345', '2001');

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/check-nickname')
            && $request->method() === 'POST'
            && $request['game_code'] === 'mobile-legends'
            && ! isset($request['sandbox_mode']));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/mobile-legends/purchase-limit')
            && $request->data() === ['customer_target' => '12345', 'customer_target_zone' => '2001']);
    }

    public function test_webhook_signature_is_case_insensitive_hex_hmac_sha256(): void
    {
        $melo = app(MeloService::class);
        $body = '{"buyer_trx_id":"INV1"}';
        $signature = hash_hmac('sha256', $body, 'test-webhook-secret');

        $this->assertTrue($melo->isValidWebhookSignature($body, $signature));
        $this->assertTrue($melo->isValidWebhookSignature($body, strtoupper($signature)));
        $this->assertFalse($melo->isValidWebhookSignature($body.' ', $signature));
        $this->assertFalse($melo->isValidWebhookSignature($body, null));
        $this->assertFalse($melo->isValidWebhookSignature($body, ''));
    }
}
