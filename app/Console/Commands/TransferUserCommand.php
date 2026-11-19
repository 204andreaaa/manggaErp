<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Project;
use App\Models\User;
use App\Services\ErpTenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Hands everything one account is linked to (documents it made, approvals and
 * approval steps assigned to it, kasbon, notifications, ...) over to another
 * account, in the master database and in every project database, and can then
 * delete the old account. Without --execute it only shows what would move.
 */
class TransferUserCommand extends Command
{
    protected $signature = 'users:transfer {from : username lama} {to : username pengganti} {--delete : hapus akun lama setelah dipindah} {--execute : benar-benar jalankan (tanpa ini hanya pratinjau)}';

    protected $description = 'Pindahkan semua data milik satu user ke user lain, lalu (opsional) hapus user lama';

    /** Integer columns that point at users.id */
    public const USER_COLUMNS = [
        'user_id', 'actual_approver_id', 'assigned_to_user_id', 'created_by_id', 'updated_by_id', 'kam_user_id',
        'owner_id', 'paid_by_id', 'request_by_id', 'settled_by_id', 'submitted_by_id', 'verified_by_id',
    ];

    /** Handled on their own: memberships and the employee profile itself */
    private const SKIP_TABLES = ['role_user', 'project_user', 'employees', 'sessions', 'personal_access_tokens', 'users'];

    public function handle(): int
    {
        $from = User::on('master')->where('username', $this->argument('from'))->first();
        $to = User::on('master')->where('username', $this->argument('to'))->first();
        if (!$from || !$to) {
            $this->error('Username tidak ditemukan: '.(!$from ? $this->argument('from') : $this->argument('to')));

            return self::FAILURE;
        }
        if ($from->id === $to->id) {
            $this->error('User lama dan pengganti sama.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $fromEmployee = Employee::on('master')->where('user_id', $from->id)->value('id');
        $toEmployee = Employee::on('master')->where('user_id', $to->id)->value('id');

        $this->info(($execute ? 'Memindahkan' : 'Pratinjau').": {$from->username} (#{$from->id}) -> {$to->username} (#{$to->id})");

        $total = $this->moveIn('master', $from->id, $to->id, $fromEmployee, $toEmployee, $execute);

        $done = [];
        foreach (Project::whereNotNull('db_name')->get() as $project) {
            if (isset($done[$project->db_name])) {
                continue;
            }
            $done[$project->db_name] = true;
            ErpTenantManager::switchToProject($project);
            $total += $this->moveIn('tenant', $from->id, $to->id, $fromEmployee, $toEmployee, $execute, $project->name);
        }

        if ($execute) {
            // the replacement keeps project access the old account had
            $to->projects()->syncWithoutDetaching($from->projects()->pluck('projects.id')->all());
        }

        if ($this->option('delete')) {
            if ($execute) {
                DB::connection('master')->transaction(function () use ($from) {
                    Employee::on('master')->where('user_id', $from->id)->delete();
                    $from->roles()->detach();
                    $from->projects()->detach();
                    $from->delete();
                });
                $this->info("Akun {$from->username} dihapus.");
            } else {
                $this->line("Akun {$from->username} akan dihapus.");
            }
        }

        $this->line("{$total} baris ".($execute ? 'dipindahkan.' : 'akan dipindahkan. Tambahkan --execute untuk menjalankan.'));

        return self::SUCCESS;
    }

    private function moveIn(string $conn, int $fromId, int $toId, ?int $fromEmployee, ?int $toEmployee, bool $execute, ?string $label = null): int
    {
        $db = DB::connection($conn);
        $columns = $db->select(
            'SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ("int", "bigint", "mediumint", "smallint")',
        );

        $moves = [];
        foreach ($columns as $col) {
            if (in_array($col->t, self::SKIP_TABLES, true)) {
                continue;
            }
            if (in_array($col->c, self::USER_COLUMNS, true)) {
                $moves[] = [$col->t, $col->c, $fromId, $toId];
            } elseif ($col->c === 'employee_id' && $fromEmployee) {
                $moves[] = [$col->t, $col->c, $fromEmployee, $toEmployee];
            }
        }

        $total = 0;
        $db->transaction(function () use ($db, $moves, $execute, $conn, $label, &$total) {
            foreach ($moves as [$table, $column, $old, $new]) {
                $count = $db->table($table)->where($column, $old)->count();
                if (!$count) {
                    continue;
                }
                if ($new === null) {
                    $this->warn("  {$table}.{$column}: {$count} baris tidak bisa dipindah (user pengganti belum punya data karyawan)");
                    continue;
                }
                if ($execute) {
                    $db->table($table)->where($column, $old)->update([$column => $new]);
                }
                $this->line('  '.($label ?? $conn)." {$table}.{$column}: {$count}");
                $total += $count;
            }
        });

        return $total;
    }
}
