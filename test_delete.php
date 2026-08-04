<?php
require 'vendor/autoload.php';
require_once 'bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::setDefaultConnection('tenant');
// Assuming project 1 is used
$config = \App\Models\Erp\ErpApprovalConfig::first();
if ($config) {
    try {
        $config->delete();
        echo "Deleted successfully.\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No config found.\n";
}
