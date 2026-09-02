<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force tenant connection
config(['database.connections.tenant' => array_merge(
    config('database.connections.mysql'),
    ['database' => 'mandau_db']
)]);
DB::purge('tenant');

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// 1. Drop Tables
Schema::connection('tenant')->disableForeignKeyConstraints();
Schema::connection('tenant')->dropIfExists('erp_budget_ledgers');
Schema::connection('tenant')->dropIfExists('erp_work_items');
Schema::connection('tenant')->dropIfExists('erp_sub_projects');
Schema::connection('tenant')->dropIfExists('erp_master_projects');
Schema::connection('tenant')->dropIfExists('erp_budget_parents');
Schema::connection('tenant')->enableForeignKeyConstraints();

// 2. Delete Migrations
DB::connection('tenant')->table('migrations')
    ->where('migration', 'like', '%erp_budget%')
    ->orWhere('migration', 'like', '%erp_sub_project%')
    ->orWhere('migration', 'like', '%erp_work_item%')
    ->orWhere('migration', 'like', '%erp_master_project%')
    ->delete();

echo "Cleaned up!\n\n";

// 3. Run Migrate
$exitCode = Artisan::call('migrate', [
    '--database' => 'tenant',
    '--path' => 'database/migrations/tenant',
    '--force' => true,
]);

echo "Migration output:\n";
echo Artisan::output();

if ($exitCode === 0) {
    // 4. Run Seeder
    echo "\nRunning Seeder:\n";
    Artisan::call('db:seed', [
        '--class' => 'Database\Seeders\ErpBudgetSeeder',
        '--force' => true,
    ]);
    echo Artisan::output();
}
