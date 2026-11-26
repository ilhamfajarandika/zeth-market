<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * phpunit.xml mengisi ADMIN_PATH=kelola-test, sehingga test ini juga membuktikan
 * panel mengikuti path dari .env dan path default /admin tidak terbuka.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_PATH = '/kelola-test';

    public function test_default_admin_path_is_not_reachable_when_a_custom_path_is_configured(): void
    {
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
    }

    public function test_admin_login_page_uses_the_logo_favicon(): void
    {
        $favicon = asset('favicon-32x32.png');

        $this->get(self::ADMIN_PATH.'/login')
            ->assertOk()
            ->assertSee('rel="icon" href="'.$favicon.'"', false)
            ->assertSee('src="'.$favicon.'"', false);
    }

    public function test_admin_login_page_uses_the_landing_colors_in_dark_mode(): void
    {
        $this->get(self::ADMIN_PATH.'/login')
            ->assertOk()
            ->assertSee('--default-theme-mode: dark', false)
            ->assertSee('--primary-600:'.Color::convertToOklch('#e8174a'), false)
            ->assertSee('--gray-950:'.Color::convertToOklch('#09050f'), false);
    }

    public function test_guest_is_redirected_to_login_on_the_configured_admin_path(): void
    {
        $this->get(self::ADMIN_PATH)->assertRedirect(self::ADMIN_PATH.'/login');
        $this->get(self::ADMIN_PATH.'/login')->assertOk();
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->get(self::ADMIN_PATH)
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel_resources(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['/admin', '/admin/orders', '/admin/products', '/admin/game-accounts', '/admin/games', '/admin/categories', '/admin/users'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
