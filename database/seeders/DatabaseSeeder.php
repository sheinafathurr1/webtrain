<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);

        $student = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@webtrain.test',
            'email_verified_at' => now(),
        ]);

        $student->assignRole('Student');
    }
}
