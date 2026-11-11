<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_member_cannot_access_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel_resources(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['/admin', '/admin/orders', '/admin/products', '/admin/game-accounts', '/admin/games', '/admin/categories'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
