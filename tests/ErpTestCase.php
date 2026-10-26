<?php

namespace Tests;

use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Base for ERP feature tests. The master and tenant test databases are rebuilt
 * from the migrations once per test run; every test then runs inside a
 * transaction on both connections that is rolled back afterwards.
 */
abstract class ErpTestCase extends TestCase
{
    private const CONNECTIONS = ['master', 'tenant'];

    private static bool $migrated = false;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useSeparateDatabasesIfAsked();

        // Migrations first (MySQL commits on DDL), then the per-test transactions.
        if (!self::$migrated) {
            Artisan::call('migrate:fresh', ['--database' => 'master', '--path' => 'database/migrations/master', '--force' => true]);
            Artisan::call('migrate:fresh', ['--database' => 'tenant', '--path' => 'database/migrations/tenant', '--force' => true]);
            self::$migrated = true;
        }
        $this->beginTestTransactions();

        $tenant = config('database.connections.tenant');
        $this->project = Project::create([
            'name' => 'Test Project',
            'db_name' => $tenant['database'],
            'db_host' => $tenant['host'],
            'db_port' => (string) $tenant['port'],
            'db_username' => $tenant['username'],
            'db_password' => $tenant['password'],
            'is_active' => true,
        ]);
    }

    /**
     * TEST_DB_SUFFIX=foo runs on mangga_test_master_foo / mangga_test_tenant_foo
     * (created if missing), so several test runs can go at the same time
     * without rebuilding each other's databases.
     */
    private function useSeparateDatabasesIfAsked(): void
    {
        $suffix = preg_replace('/[^a-z0-9_]/', '', strtolower((string) getenv('TEST_DB_SUFFIX')));
        if ($suffix === '') {
            return;
        }

        $server = config('database.connections.master');
        $pdo = new \PDO("mysql:host={$server['host']};port={$server['port']}", $server['username'], $server['password']);
        foreach (self::CONNECTIONS as $name) {
            $database = "mangga_test_{$name}_{$suffix}";
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            config(["database.connections.{$name}.database" => $database]);
            $this->app['db']->purge($name);
        }
    }

    private function beginTestTransactions(): void
    {
        $db = $this->app['db'];
        foreach (self::CONNECTIONS as $name) {
            $connection = $db->connection($name);
            $dispatcher = $connection->getEventDispatcher();
            $connection->unsetEventDispatcher();
            $connection->beginTransaction();
            $connection->setEventDispatcher($dispatcher);
        }

        $this->beforeApplicationDestroyed(function () use ($db) {
            foreach (self::CONNECTIONS as $name) {
                $connection = $db->connection($name);
                $dispatcher = $connection->getEventDispatcher();
                $connection->unsetEventDispatcher();
                while ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }
                $connection->setEventDispatcher($dispatcher);
                $connection->disconnect();
            }
        });
    }

    /**
     * A user with the given role. `$permissions` are the role's permission keys
     * (e.g. 'request_forms.create'); superadmin/admin pass every check anyway.
     */
    protected function makeUser(string $roleSlug = 'staff', array $permissions = [], array $attributes = []): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => Str::headline($roleSlug)]);
        if ($permissions) {
            $role->permissions = array_values(array_unique(array_merge($role->permissions ?? [], $permissions)));
            $role->save();
        }

        $user = User::create(array_merge([
            'name' => Str::headline($roleSlug).' '.Str::random(4),
            'username' => $roleSlug.'_'.Str::lower(Str::random(8)),
            'email' => Str::lower(Str::random(10)).'@test.local',
            'password' => bcrypt('secret'),
            'status' => 'active',
        ], $attributes));
        $user->roles()->attach($role->id);
        $this->project->users()->attach($user->id);

        return $user->fresh();
    }

    /** Log in as `$user` inside the test project (what picking a project does). */
    protected function actingInProject(User $user): static
    {
        return $this->actingAs($user)->withSession(['project_id' => $this->project->id]);
    }
}
