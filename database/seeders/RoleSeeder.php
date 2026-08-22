<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles used by the platform. Instructor is seeded now so future
     * phases can attach permissions to it without a migration.
     */
    private const ROLES = ['Admin', 'Instructor', 'Student'];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
