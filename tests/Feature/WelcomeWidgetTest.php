<?php

namespace Tests\Feature;

use App\Filament\Widgets\WelcomeWidget;
use App\Models\User;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WelcomeWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_widget_spans_full_width(): void
    {
        $this->assertSame('full', (new WelcomeWidget)->getColumnSpan());
    }

    public function test_welcome_widget_greets_the_logged_in_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(WelcomeWidget::class)->assertSee($admin->name);
    }

    public function test_welcome_card_renders_before_the_stats_on_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(Dashboard::class)->assertSeeInOrder(['WelcomeWidget', 'MeloBalanceOverview', 'MeloNicknamePlanOverview']);
    }
}
