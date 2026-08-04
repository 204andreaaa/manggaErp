<?php
require 'vendor/autoload.php';
require_once 'bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tenant = DB::connection('tenant');
$migrations = $tenant->table('migrations')->where('migration', 'like', '%erp_approval_configs%')->pluck('migration')->toArray();
print_r($migrations);

// Let's also check if table exists
$tables = $tenant->select('SHOW TABLES LIKE "%erp_approval_configs%"');
print_r($tables);

// If migration exists but table doesn't, drop the migration record so we can re-migrate
if (!empty($migrations) && empty($tables)) {
    echo "Migration record exists but table missing. Deleting record...\n";
    $tenant->table('migrations')->where('migration', 'like', '%erp_approval_configs%')->delete();
    echo "Deleted.\n";
}
