<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCustomerResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_customer_pages(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get("/admin/users/{$member->id}")->assertForbidden();
    }

    public function test_list_shows_customers_but_not_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['role' => 'member']);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$customer])
            ->assertCanNotSeeTableRecords([$admin])
            ->assertActionDoesNotExist('create');
    }

    public function test_list_can_be_searched_and_shows_order_count(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $budi = User::factory()->create(['role' => 'member', 'name' => 'Budi Santoso']);
        $sari = User::factory()->create(['role' => 'member', 'name' => 'Sari Wulandari']);
        Order::factory()->count(2)->create(['user_id' => $budi->id]);

        Livewire::test(ListUsers::class)
            ->searchTable('Budi')
            ->assertCanSeeTableRecords([$budi])
            ->assertCanNotSeeTableRecords([$sari])
            ->assertTableColumnStateSet('orders_count', 2, $budi);
    }

    public function test_admin_record_cannot_be_viewed_and_customers_are_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['role' => 'member']);
        $this->actingAs($admin);

        $this->get("/admin/users/{$admin->id}")->assertNotFound();
        $this->get("/admin/users/{$customer->id}")->assertOk();
        $this->get("/admin/users/{$customer->id}/edit")->assertNotFound();
        $this->get('/admin/users/create')->assertNotFound();

        Livewire::test(ViewUser::class, ['record' => $customer->getKey()])
            ->assertSee($customer->email)
            ->assertDontSee($customer->password);
    }
}
