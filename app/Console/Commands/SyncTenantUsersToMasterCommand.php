<?php

namespace App\Console\Commands;

use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncTenantUsersToMasterCommand extends Command
{
    protected $signature = 'master:sync-users
        {project_id : ID project sumber}
        {--execute : Simpan hasil sinkronisasi; tanpa opsi ini hanya preview}
        {--project-role=admin : Role akses pada user_projects}';

    protected $description = 'Salin/refresh akun tenant ke master tanpa menghapus data sumber';

    public function handle(): int
    {
        $projectId = (int) $this->argument('project_id');

        try {
            $project = TenantManager::switchToProject($projectId, false);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if (!Schema::connection('tenant')->hasTable('users')) {
            $this->error("Tenant {$project->db_name} tidak memiliki tabel users.");
            return self::FAILURE;
        }

        $masterColumns = Schema::connection('master')->getColumnListing('users');
        $tenantColumns = Schema::connection('tenant')->getColumnListing('users');
        $syncColumns = array_values(array_intersect([
            'name', 'username', 'email', 'phone', 'position', 'signature_path',
            'password', 'warehouse_id', 'status', 'role', 'company_id',
            'email_verified_at', 'remember_token', 'created_at', 'updated_at',
        ], $masterColumns, $tenantColumns));

        $tenantCount = DB::connection('tenant')->table('users')->count();
        $masterCount = DB::connection('master')->table('users')->count();

        $this->info("Sumber: {$project->name} ({$project->db_name})");
        $this->line("User tenant: {$tenantCount}; user master saat ini: {$masterCount}");
        $this->line('Kolom yang disinkronkan: '.implode(', ', $syncColumns));

        if (!$this->option('execute')) {
            $this->warn('Preview saja. Jalankan master:migrate --execute lebih dulu, lalu ulangi dengan --execute.');
            return self::SUCCESS;
        }

        DB::connection('master')->transaction(function () use ($projectId, $syncColumns) {
            DB::connection('tenant')->table('users')->orderBy('id')->chunkById(200, function ($users) use ($projectId, $syncColumns) {
                foreach ($users as $user) {
                    $source = (array) $user;
                    $payload = array_intersect_key($source, array_flip($syncColumns));

                    DB::connection('master')->table('users')->updateOrInsert(
                        ['id' => $user->id],
                        $payload
                    );
                    DB::connection('master')->table('project_user')->updateOrInsert(
                        ['user_id' => $user->id, 'project_id' => $projectId],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
        });

        $this->info('Sinkronisasi selesai. Tidak ada user tenant yang dihapus atau diubah.');
        return self::SUCCESS;
    }
}
