<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NicknameCheckTest extends TestCase
{
    use RefreshDatabase;

    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.melo.sandbox' => false]);

        $this->game = Game::factory()->withZone()->create(['melo_game_code' => 'mobile-legends']);
    }

    /**
     * Stub satu-satunya untuk /check-nickname. Dipanggil per test: stub pertama yang terdaftar yang menang.
     *
     * @param  array<string, mixed>  $body
     */
    private function fakeMelo(int $status = 200, array $body = [
        'success' => true,
        'data' => ['username' => 'Example Player', 'region' => 'ID', 'billing' => ['charged_mc' => 0]],
    ]): void
    {
        Http::fake(['*/check-nickname' => Http::response($body, $status)]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function checkNickname(array $data = [], ?Game $game = null): TestResponse
    {
        return $this->postJson(
            route('nickname.check', $game ?? $this->game),
            $data + ['target_user_id' => '123456789', 'target_zone_id' => '2001'],
        );
    }

    public function test_returns_only_username_and_region_for_a_found_player(): void
    {
        $this->fakeMelo();

        $this->checkNickname()
            ->assertOk()
            ->assertExactJson(['username' => 'Example Player', 'region' => 'ID']);

        Http::assertSent(fn (Request $request): bool => $request->data()['game_code'] === 'mobile-legends'
            && $request->data()['customer_target'] === '123456789'
            && $request->data()['customer_target_zone'] === '2001');
    }

    public function test_player_not_found_returns_null_fields_without_an_error(): void
    {
        $this->fakeMelo(404, ['success' => false]);

        $this->checkNickname()->assertOk()->assertExactJson(['username' => null, 'region' => null]);
    }

    public function test_zone_is_not_sent_for_games_without_a_zone(): void
    {
        $this->fakeMelo();
        $game = Game::factory()->create(['melo_game_code' => 'pubg-mobile']);

        $this->postJson(route('nickname.check', $game), ['target_user_id' => '123456789'])->assertOk();

        Http::assertSent(fn (Request $request): bool => $request->data()['customer_target'] === '123456789'
            && ! array_key_exists('customer_target_zone', $request->data()));
    }

    public function test_game_without_a_melo_code_is_not_found_and_sends_nothing(): void
    {
        $game = Game::factory()->create(['melo_game_code' => null]);

        $this->checkNickname(game: $game)->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_inactive_game_is_not_found_and_sends_nothing(): void
    {
        $this->game->update(['is_active' => false]);

        $this->checkNickname()->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_invalid_id_format_is_rejected_before_calling_melo(): void
    {
        $this->checkNickname(['target_user_id' => 'abc'])->assertUnprocessable()->assertJsonValidationErrors('target_user_id');
        $this->checkNickname(['target_zone_id' => 'x'])->assertUnprocessable()->assertJsonValidationErrors('target_zone_id');

        Http::assertNothingSent();
    }

    public function test_sandbox_mode_sends_nothing_and_returns_null_fields(): void
    {
        config(['services.melo.sandbox' => true]);

        $this->checkNickname()->assertOk()->assertExactJson(['username' => null, 'region' => null]);

        Http::assertNothingSent();
    }

    public function test_requests_are_throttled_per_ip(): void
    {
        $this->fakeMelo();

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->checkNickname()->assertOk();
        }

        $this->checkNickname()->assertTooManyRequests();
    }

    public function test_game_page_exposes_the_nickname_url_only_when_the_game_has_a_melo_code(): void
    {
        $this->get(route('game.show', $this->game))
            ->assertInertia(fn (Assert $page) => $page->where('nicknameUrl', route('nickname.check', $this->game)));

        $withoutCode = Game::factory()->create(['melo_game_code' => null]);

        $this->get(route('game.show', $withoutCode))
            ->assertInertia(fn (Assert $page) => $page->where('nicknameUrl', null));
    }
}
