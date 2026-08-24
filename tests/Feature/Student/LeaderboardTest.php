<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_ranks_students_by_points_and_excludes_non_students(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $top = User::factory()->create(['email_verified_at' => now(), 'total_points' => 300]);
        $top->assignRole('Student');

        $middle = User::factory()->create(['email_verified_at' => now(), 'total_points' => 150]);
        $middle->assignRole('Student');

        $admin = User::factory()->create(['email_verified_at' => now(), 'total_points' => 999]);
        $admin->assignRole('Admin');

        $viewer = User::factory()->create(['email_verified_at' => now(), 'total_points' => 10]);
        $viewer->assignRole('Student');

        $component = Volt::actingAs($viewer)->test('pages.leaderboard');

        $leaderboard = $component->get('leaderboard');

        $this->assertCount(3, $leaderboard);
        $this->assertSame($top->id, $leaderboard->first()->id);
        $this->assertFalse($leaderboard->contains('id', $admin->id));
    }

    public function test_leaderboard_page_is_accessible_to_authenticated_users(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('Student');

        $this->actingAs($user)
            ->get(route('leaderboard'))
            ->assertOk()
            ->assertSee('Leaderboard');
    }
}
