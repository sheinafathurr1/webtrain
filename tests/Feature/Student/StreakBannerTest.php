<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class StreakBannerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_banner_shows_when_streak_is_at_risk(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

        $user = User::factory()->create([
            'current_streak' => 3,
            'last_activity_date' => Carbon::yesterday(),
        ]);

        Volt::actingAs($user)->test('pages.dashboard')
            ->assertSee('Streak 3 hari kamu bakal putus hari ini!');
    }

    public function test_banner_hidden_when_already_active_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

        $user = User::factory()->create([
            'current_streak' => 3,
            'last_activity_date' => Carbon::today(),
        ]);

        Volt::actingAs($user)->test('pages.dashboard')
            ->assertDontSee('bakal putus hari ini');
    }

    public function test_banner_hidden_when_no_active_streak(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-24 12:00:00'));

        $user = User::factory()->create([
            'current_streak' => 0,
            'last_activity_date' => Carbon::yesterday(),
        ]);

        Volt::actingAs($user)->test('pages.dashboard')
            ->assertDontSee('bakal putus hari ini');
    }
}
