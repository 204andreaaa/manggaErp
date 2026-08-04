<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class MasterMigrateCommand extends Command
{
    protected $signature = 'master:migrate
        {--execute : Jalankan migration; tanpa opsi ini hanya preview}
        {--force : Izinkan eksekusi pada environment production}';

    protected $description = 'Preview atau jalankan migration khusus database master';

    public function handle(): int
    {
        $files = collect(File::files(database_path('migrations/master')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->sortBy(fn ($file) => $file->getFilename())
            ->values();

        $ran = Schema::connection('master')->hasTable('migrations')
            ? DB::connection('master')->table('migrations')->pluck('migration')->all()
            : [];
        $pending = $files->reject(fn ($file) => in_array(
            pathinfo($file->getFilename(), PATHINFO_FILENAME),
            $ran,
            true
        ));

        $this->info('Master database: '.config('database.connections.master.database'));
        $this->line('Migration pending: '.$pending->count());
        foreach ($pending as $file) {
            $this->line(' - '.pathinfo($file->getFilename(), PATHINFO_FILENAME));
        }

        if (!$this->option('execute')) {
            $this->warn('Preview saja. Tambahkan --execute setelah backup diperiksa.');
            return self::SUCCESS;
        }
        if ($pending->isEmpty()) {
            return self::SUCCESS;
        }

        $exitCode = Artisan::call('migrate', [
            '--database' => 'master',
            '--path' => $files->map->getRealPath()->all(),
            '--realpath' => true,
            '--force' => (bool) $this->option('force'),
        ]);
        $this->output->write(Artisan::output());

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
