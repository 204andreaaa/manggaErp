<?php

namespace Database\Seeders;

use Database\Seeders\Core\CompanySeeder;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\Erp\ErpSetupSeeder;
use Database\Seeders\ErpProductSeeder;
use Database\Seeders\MasterUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            RoleSeeder::class,
            ErpSetupSeeder::class,
            \Database\Seeders\Core\UserSeeder::class,
            ErpProductSeeder::class,
            MasterUserSeeder::class,
            ErpBudgetSeeder::class,
            ErpApprovalConfigSeeder::class,
        ]);
    }
}
