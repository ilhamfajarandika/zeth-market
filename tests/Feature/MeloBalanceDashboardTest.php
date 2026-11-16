<?php

namespace Tests\Feature;

use App\Filament\Widgets\MeloBalanceOverview;
use App\Filament\Widgets\PaymentOverview;
use App\Models\Order;
use App\Models\User;
use App\Services\Supplier\MeloBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MeloBalanceDashboardTest extends TestCase
{
    use RefreshDatabase;

    private const BASE_URL = 'https://melo.test/api/v1/h2h';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => self::BASE_URL,
            'services.melo.api_key' => 'k',
            'services.melo.secret_key' => 's',
            'services.melo.low_balance_threshold' => 500000,
        ]);
        Cache::flush();
        $this->fakeMelo();
    }

    private float|int $balance = 0;

    private bool $sandbox = false;

    private int $status = 200;

    /** @var array<string, mixed>|null */
    private ?array $rawBody = null;

    protected function fakeMelo(): void
    {
        Http::fake([self::BASE_URL.'/profile/balance' => fn () => Http::response($this->rawBody ?? ($this->status === 200 ? ['success' => true, 'data' => [
            'h2h_balance' => $this->balance,
            'is_sandbox_mode' => $this->sandbox,
            'usd_idr_rate' => 17000,
            'display_usd_idr_rate' => null,
            'display_rate_updated_at' => null,
            'h2h_balance_usd' => null,
        ]] : ['message' => 'error']), $this->status)]);
    }

    private function fakeBalance(float|int $balance, bool $sandbox = false, int $status = 200): void
    {
        $this->balance = $balance;
        $this->sandbox = $sandbox;
        $this->status = $status;
    }

    public function test_levels_follow_threshold(): void
    {
        $service = app(MeloBalanceService::class);

        $this->assertSame('ok', $service->level(500001));
        $this->assertSame('low', $service->level(500000));
        $this->assertSame('low', $service->level(250001));
        $this->assertSame('critical', $service->level(250000));
        $this->assertSame('critical', $service->level(0));
    }

    public function test_balance_is_cached_and_truncated_to_whole_rupiah(): void
    {
        $this->fakeBalance(123456.78);

        $service = app(MeloBalanceService::class);

        $this->assertSame(123456, $service->get()['balance']);
        $service->get();
        Http::assertSentCount(1);

        $service->get(fresh: true);
        Http::assertSentCount(2);
    }

    public function test_balance_is_null_when_melo_fails_or_returns_garbage(): void
    {
        $this->fakeBalance(0, status: 401);
        $this->assertNull(app(MeloBalanceService::class)->get());

        $this->status = 200;
        $this->rawBody = ['success' => true, 'data' => []];
        $this->assertNull(app(MeloBalanceService::class)->get(fresh: true));
    }

    public function test_widget_shows_balance_with_level_message(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->fakeBalance(2000000);
        Livewire::test(MeloBalanceOverview::class)->assertSee('2.000.000')->assertSee('Saldo aman');

        Cache::flush();
        $this->fakeBalance(400000);
        Livewire::test(MeloBalanceOverview::class)->assertSee('400.000')->assertSee('Saldo menipis');

        Cache::flush();
        $this->fakeBalance(100000);
        Livewire::test(MeloBalanceOverview::class)->assertSee('Saldo hampir habis');
    }

    public function test_widget_degrades_gracefully_when_balance_unavailable(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->fakeBalance(0, status: 500);

        Livewire::test(MeloBalanceOverview::class)->assertSee('Tidak dapat dimuat');
    }

    public function test_dashboard_renders_for_admin(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->fakeBalance(900000);

        $this->get('/admin')->assertOk();
    }

    public function test_payment_widget_counts_orders(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Order::factory()->paid()->create(['total' => 20000]);
        Order::factory()->create(['status' => 'pending', 'expired_at' => now()->addMinutes(5)]);
        Order::factory()->create(['status' => 'pending', 'expired_at' => now()->subMinute()]);
        Order::factory()->create(['status' => 'expired']);

        Livewire::test(PaymentOverview::class)
            ->assertSee('20.000')
            ->assertSee('1 pembayaran masuk')
            ->assertSee('Menunggu pembayaran');
    }

    public function test_command_notifies_admins_once_per_level_and_escalates(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create(['role' => 'member']);

        $this->fakeBalance(400000);
        $this->artisan('melo:check-balance')->assertSuccessful();
        $this->assertCount(1, $admin->notifications);
        $this->assertSame('Saldo Melo menipis', $admin->notifications->first()->data['title']);
        $this->assertCount(0, $member->notifications);

        $this->artisan('melo:check-balance')->assertSuccessful();
        $this->assertCount(1, $admin->fresh()->notifications);

        $this->fakeBalance(100000);
        $this->artisan('melo:check-balance')->assertSuccessful();
        $this->assertCount(2, $admin->fresh()->notifications);
        $this->assertSame('Saldo Melo hampir habis', $admin->fresh()->notifications->first()->data['title']);
    }

    public function test_command_alerts_again_after_balance_recovers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->fakeBalance(400000);
        $this->artisan('melo:check-balance')->assertSuccessful();

        $this->fakeBalance(900000);
        $this->artisan('melo:check-balance')->assertSuccessful();

        $this->fakeBalance(400000);
        $this->artisan('melo:check-balance')->assertSuccessful();

        $this->assertCount(2, $admin->fresh()->notifications);
    }

    public function test_command_does_not_alert_in_sandbox_or_when_balance_is_healthy(): void
    {
        $admin = User::factory()->admin()->create();

        $this->fakeBalance(100, sandbox: true);
        $this->artisan('melo:check-balance')->assertSuccessful();

        $this->fakeBalance(900000);
        $this->artisan('melo:check-balance')->assertSuccessful();

        $this->assertCount(0, $admin->fresh()->notifications);
    }

    public function test_command_fails_without_notifying_when_melo_is_unreachable(): void
    {
        $admin = User::factory()->admin()->create();
        $this->fakeBalance(0, status: 500);

        $this->artisan('melo:check-balance')->assertFailed();

        $this->assertCount(0, $admin->fresh()->notifications);
    }

    public function test_command_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('melo:check-balance')->assertSuccessful();
    }
}
