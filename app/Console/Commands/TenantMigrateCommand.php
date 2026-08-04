<?php

namespace App\Console\Commands;

use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class TenantMigrateCommand extends Command
{
    protected $signature = 'tenant:migrate
        {project_id : ID project pada database master}
        {--execute : Jalankan migration; tanpa opsi ini hanya preview}
        {--force : Izinkan eksekusi pada environment production}';

    protected $description = 'Preview atau jalankan migration operasional pada satu tenant';

    public function handle(): int
    {
        try {
            $projectId = (int) $this->argument('project_id');
            $project = \App\Models\Project::findOrFail($projectId);
            \App\Services\ErpTenantManager::switchToProject($project);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $files = collect(File::files(database_path('migrations/tenant')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        $ran = Schema::connection('tenant')->hasTable('migrations')
            ? DB::connection('tenant')->table('migrations')->pluck('migration')->all()
            : [];
        $pending = $files->reject(fn ($file) => in_array(
            pathinfo($file->getFilename(), PATHINFO_FILENAME),
            $ran,
            true
        ));

        $this->info("Tenant: {$project->name} ({$project->db_name})");
        $this->line('Migration pending: '.$pending->count());
        foreach ($pending as $file) {
            $this->line(' - '.pathinfo($file->getFilename(), PATHINFO_FILENAME));
        }

        if (!$this->option('execute')) {
            $this->warn('Preview saja. Tambahkan --execute setelah backup diperiksa.');
            return self::SUCCESS;
        }

        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        $this->output->write(Artisan::output());

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
