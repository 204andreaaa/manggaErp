<?php

namespace App\Console\Commands;

use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class TenantsMigrateAllCommand extends Command
{
    protected $signature = 'tenants:migrate-all
        {--execute : Jalankan migration; tanpa opsi ini hanya preview}
        {--force : Izinkan eksekusi pada environment production}';

    protected $description = 'Jalankan migration operasional pada SEMUA database tenant aktif sekaligus';

    public function handle(): int
    {
        $projects = \App\Models\Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->info('Tidak ada project/tenant aktif ditemukan.');
            return self::SUCCESS;
        }

        $this->info("Menemukan {$projects->count()} tenant aktif.");

        foreach ($projects as $project) {
            $this->newLine();
            $this->info("========================================");
            $this->info("Memproses Tenant: {$project->name} (DB: {$project->db_name})");
            $this->info("========================================");

            $exitCode = Artisan::call('tenant:migrate', [
                'project_id' => $project->id,
                '--execute' => $this->option('execute'),
                '--force' => $this->option('force'),
            ]);

            $this->output->write(Artisan::output());

            if ($exitCode !== 0) {
                $this->error("Gagal melakukan migrasi pada tenant: {$project->name}");
                
                if (!$this->confirm('Apakah Anda ingin melanjutkan migrasi ke tenant berikutnya?', true)) {
                    return self::FAILURE;
                }
            }
        }

        $this->newLine();
        $this->info('Proses migrasi untuk semua tenant selesai.');
        return self::SUCCESS;
    }
}
