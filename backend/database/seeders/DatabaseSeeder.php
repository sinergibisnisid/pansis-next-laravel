<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganizationSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            VaultSeeder::class,
            DeviceSeeder::class,
            WorkingTimeSeeder::class,
        ]);
    }
}
