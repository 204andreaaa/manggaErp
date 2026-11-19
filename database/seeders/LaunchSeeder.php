<?php

namespace Database\Seeders;

use Database\Seeders\Core\DepartmentSeeder;
use Database\Seeders\Launch\RolePermissionSeeder;
use Database\Seeders\Launch\UserRosterSeeder;
use Illuminate\Database\Seeder;

/**
 * Reference data for a real server. Unlike DatabaseSeeder (demo data, empties
 * budgets/products/approval configs) this one only adds or completes data and
 * is safe to run on every deploy. RolePermissionSeeder puts the division and
 * job roles back to the standard permissions (edits made in Roles to those roles
 * are overwritten):
 *
 *   php artisan db:seed --class="Database\Seeders\LaunchSeeder" --force
 */
class LaunchSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            RolePermissionSeeder::class,
            UserRosterSeeder::class,
        ]);
    }
}
