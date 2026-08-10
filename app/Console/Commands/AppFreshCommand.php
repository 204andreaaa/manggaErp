<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppFreshCommand extends Command
{
    protected $signature = 'app:fresh {--seed : Run database seeders after migration}';

    protected $description = 'Drop all tables, run all master and tenant migrations, and optionally seed the database';

    public function handle(): int
    {
        $this->info('1. Running master database fresh migration...');
        Artisan::call('migrate:fresh', [
            '--database' => 'master',
            '--path'     => 'database/migrations/master',
            '--force'    => true,
        ]);
        $this->output->write(Artisan::output());

        $this->info('2. Running tenant database fresh migration...');
        Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--path'     => 'database/migrations/tenant',
            '--force'    => true,
        ]);
        $this->output->write(Artisan::output());

        $this->info('3. Running default database migration...');
        Artisan::call('migrate', [
            '--force' => true,
        ]);
        $this->output->write(Artisan::output());

        if ($this->option('seed')) {
            $this->info('4. Seeding database...');
            Artisan::call('db:seed', [
                '--force' => true,
            ]);
            $this->output->write(Artisan::output());
        }

        $this->info('All migrations & seeders completed successfully!');
        return self::SUCCESS;
    }
}
