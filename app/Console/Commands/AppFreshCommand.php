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
        $this->ensureDatabasesExist();

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

    private function ensureDatabasesExist(): void
    {
        $masterDb = config('database.connections.master.database', 'mandau_master');
        $tenantDb = config('database.connections.tenant.database', 'mandau_db');

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $user = config('database.connections.mysql.username', 'root');
        $pass = config('database.connections.mysql.password', '');

        try {
            $pdo = new \PDO("mysql:host={$host};port={$port}", $user, $pass);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$masterDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$tenantDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("✓ Databases `{$masterDb}` and `{$tenantDb}` checked & ready!");
        } catch (\Exception $e) {
            $this->warn("Note: " . $e->getMessage());
        }
    }
}
