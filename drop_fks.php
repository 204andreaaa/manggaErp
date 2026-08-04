<?php
require 'vendor/autoload.php';
require_once 'bootstrap/app.php';
app()->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::setDefaultConnection('tenant');
config(['database.connections.tenant.database' => 'mangga_imprima_db']);
DB::purge('tenant');

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::connection('tenant')->table('erp_approvals', function (Blueprint $table) {
    $table->dropForeign(['assigned_to_user_id']);
    $table->dropForeign(['actual_approver_id']);
});

echo "Foreign keys dropped successfully.";
