<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = scandir('database/migrations/tenant');
foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $name = str_replace('.php', '', $file);
        if (!Illuminate\Support\Facades\DB::connection('tenant')->table('migrations')->where('migration', $name)->exists()) {
            if (!str_contains($name, 'erp_budget') && !str_contains($name, 'erp_sub_project') && !str_contains($name, 'erp_master_project') && !str_contains($name, 'erp_work_item')) {
                Illuminate\Support\Facades\DB::connection('tenant')->table('migrations')->insert(['migration' => $name, 'batch' => 1]);
                echo "Inserted: $name\n";
            }
        }
    }
}
