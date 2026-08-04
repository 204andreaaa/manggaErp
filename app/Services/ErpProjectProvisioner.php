<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Exception;

class ErpProjectProvisioner
{
    public static function provision(Project $project)
    {
        $dbName = $project->db_name;
        
        // 1. Create the database
        try {
            DB::connection('master')->statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (Exception $e) {
            throw new Exception("Gagal membuat database: " . $e->getMessage());
        }

        // 2. Switch connection
        ErpTenantManager::switchToProject($project);

        // 3. Run tenant migrations
        try {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        } catch (Exception $e) {
            throw new Exception("Gagal menjalankan migrasi ERP: " . $e->getMessage());
        }

        // Switch back to master as default
        config(['database.default' => 'master']);
    }
}
