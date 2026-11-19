<?php

namespace Tests\Feature;

use App\Models\User;
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

        foreach (['', '/orders', '/products', '/game-accounts', '/games', '/categories'] as $page) {
            $this->get(self::ADMIN_PATH.$page)->assertOk();
        }
    }
}
