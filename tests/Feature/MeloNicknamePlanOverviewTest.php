<?php

namespace Tests\Feature;

use App\Filament\Widgets\MeloNicknamePlanOverview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MeloNicknamePlanOverviewTest extends TestCase
{
    private const PLAN_URL = 'https://melo.test/api/v1/h2h/check-nickname/plan';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.melo.base_url' => 'https://melo.test/api/v1/h2h',
            'services.melo.api_key' => 'k',
            'services.melo.secret_key' => 's',
        ]);
        Cache::flush();
    }

    public function test_widget_shows_plan_limits_and_caches_the_response(): void
    {
        Http::fake([self::PLAN_URL => Http::response([
            'success' => true,
            'data' => ['free_daily_limit' => 40, 'free_minute_limit' => 20, 'paid_price_mc' => 1, 'paid_minute_limit' => 20, 'resets_at' => '00:00 UTC'],
        ])]);

        Livewire::test(MeloNicknamePlanOverview::class)
            ->assertSee('40 / hari')
            ->assertSee('Maks 20 per menit, reset 00:00 UTC')
            ->assertSee('1 MC per cek');

        Livewire::test(MeloNicknamePlanOverview::class)->assertSee('40 / hari');

        Http::assertSentCount(1);
    }

    public function test_widget_degrades_gracefully_when_melo_fails(): void
    {
        Http::fake([self::PLAN_URL => Http::response(['success' => false], 500)]);

        Livewire::test(MeloNicknamePlanOverview::class)->assertSee('Tidak dapat dimuat');
    }

    public function test_widget_degrades_gracefully_when_response_is_incomplete(): void
    {
        Http::fake([self::PLAN_URL => Http::response(['success' => true, 'data' => ['free_daily_limit' => 40]])]);

        Livewire::test(MeloNicknamePlanOverview::class)->assertSee('Tidak dapat dimuat');
    }

    public function test_widget_degrades_gracefully_when_a_limit_is_null(): void
    {
        Http::fake([self::PLAN_URL => Http::response([
            'success' => true,
            'data' => ['free_daily_limit' => null, 'free_minute_limit' => 20, 'paid_price_mc' => 1, 'paid_minute_limit' => 20, 'resets_at' => '00:00 UTC'],
        ])]);

        Livewire::test(MeloNicknamePlanOverview::class)->assertSee('Tidak dapat dimuat');
    }
}
