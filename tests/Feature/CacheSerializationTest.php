<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The default test CACHE_STORE is "array" (see phpunit.xml), which
 * never actually serializes values — so a page that caches an
 * Eloquent Collection/model can pass every other test yet still 500
 * in production, because this app's cache config sets
 * serializable_classes to false: unserialize() then runs with
 * allowed_classes => false and silently turns any cached object into
 * an unusable stub on the next read. These tests force the real
 * "database" driver specifically to catch that class of regression.
 */
class CacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'database');

        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_leaderboard_survives_a_real_cache_round_trip(): void
    {
        $student = User::factory()->create(['email_verified_at' => now(), 'total_points' => 50]);
        $student->assignRole('Student');

        $this->actingAs($student);

        // First hit populates the cache, second hit reads it back.
        $this->get(route('leaderboard'))->assertOk()->assertSee($student->name);
        $this->get(route('leaderboard'))->assertOk()->assertSee($student->name);
    }

    public function test_admin_analytics_survives_a_real_cache_round_trip(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('Admin');

        $this->actingAs($admin);

        $this->get(route('admin.analytics'))->assertOk()->assertSee('Total Siswa');
        $this->get(route('admin.analytics'))->assertOk()->assertSee('Total Siswa');
    }
}
