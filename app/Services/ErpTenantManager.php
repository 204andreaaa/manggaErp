<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ErpTenantManager
{
    public static function switchToProject(Project $project)
    {
        // Setup the tenant database connection dynamically
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $project->db_host,
            'port' => $project->db_port,
            'database' => $project->db_name,
            'username' => $project->db_username,
            'password' => $project->db_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        // Purge any existing tenant connection and reconnect
        DB::purge('tenant');
        DB::reconnect('tenant');
        
        // Set default connection to tenant
        Config::set('database.default', 'tenant');
        DB::purge('mysql');
    }
}
