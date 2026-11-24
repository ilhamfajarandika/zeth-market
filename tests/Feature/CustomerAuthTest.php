<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config(['inertia.ssr.enabled' => false]);
    }

    public function test_guest_can_view_login_and_register_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));

        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_customer_can_register_and_always_gets_member_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Budi Gamer',
            'email' => 'budi@example.com',
            'whatsapp' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'budi@example.com')->firstOrFail();
        $this->assertSame(UserRole::Member, $user->role);
        $this->assertSame('081234567890', $user->whatsapp);
    }

    public function test_customer_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'secret123',
        ]);

        $this->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => 'member@example.com',
            'password' => 'secret123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->missing('auth.user.role')
                ->missing('auth.user.balance'));

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_authenticated_customer_is_redirected_from_guest_auth_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('login'))->assertRedirect();
        $this->actingAs($user)->get(route('register'))->assertRedirect();
    }
}
