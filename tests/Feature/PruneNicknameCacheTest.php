<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PruneNicknameCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_only_expired_nickname_cache_rows(): void
    {
        $prefix = config('cache.prefix');
        $expiredAt = now()->subMinute()->getTimestamp();

        DB::table('cache')->insert([
            ['key' => $prefix.'melo.nickname.expired', 'value' => 'x', 'expiration' => $expiredAt],
            ['key' => $prefix.'melo.nickname.fresh', 'value' => 'x', 'expiration' => now()->addDay()->getTimestamp()],
            ['key' => $prefix.'melo.balance.alerted_level', 'value' => 'x', 'expiration' => $expiredAt],
        ]);

        $this->artisan('melo:prune-nickname-cache')
            ->expectsOutput('Dihapus 1 baris cache cek nickname yang kedaluwarsa.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('cache', ['key' => $prefix.'melo.nickname.expired']);
        $this->assertDatabaseHas('cache', ['key' => $prefix.'melo.nickname.fresh']);
        $this->assertDatabaseHas('cache', ['key' => $prefix.'melo.balance.alerted_level']);
    }

    public function test_removes_a_cached_nickname_after_its_one_day_retention(): void
    {
        config([
            'cache.default' => 'database',
            'services.melo.nickname_live' => true,
        ]);
        Http::fake(['*/check-nickname' => Http::response([
            'success' => true,
            'data' => ['username' => 'Example Player', 'region' => 'ID'],
        ], 200)]);

        $game = Game::factory()->create(['melo_game_code' => 'mobile-legends']);
        $this->postJson(route('nickname.check', $game), ['target_user_id' => '123456789'])->assertOk();

        $this->assertSame(1, DB::table('cache')->where('key', 'like', '%melo.nickname.%')->count());

        $this->travel(25)->hours();
        $this->artisan('melo:prune-nickname-cache')->assertSuccessful();

        $this->assertSame(0, DB::table('cache')->where('key', 'like', '%melo.nickname.%')->count());
    }
}
