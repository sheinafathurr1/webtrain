<?php

namespace Tests\Feature\Console;

use App\Models\User;
use App\Notifications\StreakReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendStreakRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-08-24 18:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_notifies_students_whose_streak_is_at_risk(): void
    {
        Notification::fake();

        $atRisk = User::factory()->create([
            'current_streak' => 5,
            'last_activity_date' => Carbon::yesterday(),
        ]);
        $atRisk->assignRole('Student');

        $this->artisan('streak:remind')->assertExitCode(0);

        Notification::assertSentTo($atRisk, StreakReminderNotification::class);
    }

    public function test_does_not_notify_students_already_active_today(): void
    {
        Notification::fake();

        $active = User::factory()->create([
            'current_streak' => 5,
            'last_activity_date' => Carbon::today(),
        ]);
        $active->assignRole('Student');

        $this->artisan('streak:remind');

        Notification::assertNotSentTo($active, StreakReminderNotification::class);
    }

    public function test_does_not_notify_students_with_no_streak(): void
    {
        Notification::fake();

        $noStreak = User::factory()->create([
            'current_streak' => 0,
            'last_activity_date' => Carbon::yesterday(),
        ]);
        $noStreak->assignRole('Student');

        $this->artisan('streak:remind');

        Notification::assertNotSentTo($noStreak, StreakReminderNotification::class);
    }

    public function test_does_not_notify_non_students(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'current_streak' => 5,
            'last_activity_date' => Carbon::yesterday(),
        ]);
        $admin->assignRole('Admin');

        $this->artisan('streak:remind');

        Notification::assertNothingSentTo($admin);
    }

    public function test_does_not_double_notify_within_the_same_day(): void
    {
        Notification::fake();

        $atRisk = User::factory()->create([
            'current_streak' => 5,
            'last_activity_date' => Carbon::yesterday(),
        ]);
        $atRisk->assignRole('Student');

        $this->artisan('streak:remind');
        $this->artisan('streak:remind');

        Notification::assertSentToTimes($atRisk, StreakReminderNotification::class, 1);
    }
}
