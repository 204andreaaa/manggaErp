<?php

namespace Database\Seeders\Launch;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\RoleSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The real Mandau team (list from 2026-11-19). Safe to run again:
 *  - creates the "Head of ..." / project roles when missing, never touches the
 *    permissions of a role that already exists;
 *  - creates missing users with the starting password, but never resets the
 *    password of a user that already exists;
 *  - sets roles, position and department to what the list says;
 *  - deactivates the old demo accounts named in OLD_ACCOUNTS (nothing is deleted).
 *
 * Each person has exactly one role. Head / project roles count as members of
 * their division (Role::DIVISION_MEMBERS), and start as a copy of the division
 * role's permissions and menus; the difference between head and staff is then
 * ticked in Roles.
 */
class UserRosterSeeder extends Seeder
{
    public const STAFF_PASSWORD = '123456';
    public const SUPERADMIN_PASSWORD = 'password123';

    /** slug => name; permissions and menus start as a copy of the division role */
    public const EXTRA_ROLES = [
        'head_project' => 'Head of Project',
        'project_manager' => 'Project Manager',
        'project_coordinator' => 'Project Coordinator',
        'staff_project' => 'Staff Project',
        'head_procurement' => 'Head of Procurement',
        'head_logistic' => 'Head of Logistic',
        'head_finance' => 'Head of Finance',
        'head_hr' => 'Head of HR',
    ];

    /** username => [name, position, department code, roles] */
    public const ROSTER = [
        'barry' => ['Barry', 'CEO', 'EXEC', ['ceo']],

        'ella' => ['Ella', 'Head of Project', 'PROJ', ['head_project']],
        'atikha' => ['Atikha', 'Head of Project', 'PROJ', ['head_project']],
        'fikri' => ['Fikri', 'Head of Project', 'PROJ', ['head_project']],
        'dicky' => ['Dicky', 'Project Manager', 'PROJ', ['project_manager']],
        'radit' => ['Radit', 'Project Manager', 'PROJ', ['project_manager']],
        'jefri' => ['Jefri', 'Project Manager', 'PROJ', ['project_manager']],
        'eva' => ['Eva', 'Staff Project', 'PROJ', ['staff_project']],
        'indah' => ['Indah', 'Staff Project', 'PROJ', ['staff_project']],
        'munik' => ['Munik', 'Staff Project', 'PROJ', ['staff_project']],

        'andrea' => ['Andrea', 'Staff IT', 'IT', ['superadmin']],
        'sadrak' => ['Sadrak', 'Head of IT', 'IT', ['superadmin']],
        'irham' => ['Irham', 'Staff IT', 'IT', ['superadmin']],

        'sesa' => ['Sesa', 'General Affair', 'GA', ['general_affair']],
        'bram' => ['Bram', 'General Affair', 'GA', ['general_affair']],

        'febri' => ['Febri', 'Head of Procurement', 'PROC', ['head_procurement']],
        'silmi' => ['Silmi', 'Staff Procurement', 'PROC', ['procurement']],
        'rara' => ['Rara', 'Staff Procurement', 'PROC', ['procurement']],

        'nikmal' => ['Nikmal', 'Head of Logistic', 'LOG', ['head_logistic']],
        'nanang' => ['Nanang', 'Staff Logistic', 'LOG', ['logistik']],

        'melvien' => ['Melvien', 'Head of Finance', 'FIN', ['head_finance']],
        'lilu' => ['Lilu', 'Staff Finance', 'FIN', ['finance']],
        'ririn' => ['Ririn', 'Staff Finance', 'FIN', ['finance']],
        'ani' => ['Ani', 'Staff Finance', 'FIN', ['finance']],
        'zamzam' => ['Zamzam', 'Staff Finance', 'FIN', ['finance']],

        'kurnia' => ['Kurnia', 'Head of HR', 'HRD', ['head_hr']],
        'desti' => ['Desti', 'Staff HR', 'HRD', ['hrd']],
        'vanesa' => ['Vanesa', 'Staff HR', 'HRD', ['hrd']],
        'riza' => ['Riza', 'Staff HR', 'HRD', ['hrd']],
    ];

    /** Demo / test accounts that are not on the list. 'admin' stays as the spare super admin. */
    public const OLD_ACCOUNTS = ['budi', 'siti', 'procurement', 'ceo', 'Chenthia_Budiharto', 'admin_project', 'ga_budi'];

    public function run(): void
    {
        DB::connection('master')->transaction(function () {
            $this->ensureRoles();
            $roleIds = Role::pluck('id', 'slug');
            $departments = Department::pluck('name', 'code');
            $projectIds = Project::where('is_active', true)->pluck('id')->all();

            foreach (self::ROSTER as $username => [$name, $position, $deptCode, $roles]) {
                $missing = array_diff($roles, $roleIds->keys()->all());
                if ($missing) {
                    throw new \RuntimeException("Role belum ada: ".implode(', ', $missing).' (jalankan RoleSeeder dulu)');
                }
                if (!isset($departments[$deptCode])) {
                    throw new \RuntimeException("Departemen {$deptCode} belum ada (jalankan DepartmentSeeder dulu)");
                }

                $user = User::on('master')->where('username', $username)->first();
                $isNew = !$user;
                if ($isNew) {
                    $user = new User();
                    $user->setConnection('master');
                    $user->username = $username;
                    $user->name = $name;
                    $user->email = $username.'@mandau.id';
                    $user->password = Hash::make(in_array('superadmin', $roles, true) ? self::SUPERADMIN_PASSWORD : self::STAFF_PASSWORD);
                }
                $user->position = $position;
                $user->status = 'active';
                $user->save();

                $user->roles()->sync($roleIds->only($roles)->values()->all());
                if ($isNew && $projectIds) {
                    $user->projects()->syncWithoutDetaching($projectIds);
                }

                $employee = Employee::on('master')->firstOrNew(['user_id' => $user->id]);
                if (!$employee->exists) {
                    $employee->fill([
                        'nik' => sprintf('EMP-%04d', $user->id),
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'employment_status' => 'permanent',
                        'join_date' => now()->toDateString(),
                    ]);
                }
                $employee->position = $position;
                $employee->department = $departments[$deptCode];
                $employee->save();

                $this->say(($isNew ? 'baru   ' : 'update ')."{$username} -> {$position} / {$departments[$deptCode]}");
            }

            foreach (self::OLD_ACCOUNTS as $username) {
                $updated = User::on('master')->where('username', $username)->where('status', '!=', 'inactive')->update(['status' => 'inactive']);
                if ($updated) {
                    $this->say("nonaktif {$username}");
                }
            }

            $others = User::on('master')->where('status', 'active')
                ->whereNotIn('username', array_merge(array_keys(self::ROSTER), ['admin']))
                ->pluck('username')->all();
            if ($others) {
                $this->say('Masih aktif tapi tidak ada di daftar (cek manual): '.implode(', ', $others));
            }
        });
    }

    private function ensureRoles(): void
    {
        $divisionOf = [];
        foreach (Role::DIVISION_MEMBERS as $division => $members) {
            foreach ($members as $member) {
                $divisionOf[$member] = $division;
            }
        }

        foreach (self::EXTRA_ROLES as $slug => $name) {
            $role = Role::firstOrNew(['slug' => $slug]);
            $role->name ??= $name;

            // Copy from the division role while the job role is new or still at
            // its untouched starting point; never overwrite permissions ticked later.
            $untouched = !$role->exists
                || ($this->sorted($role->permissions) === $this->sorted(RoleSeeder::RF_FOR_EVERYONE)
                    && ($role->menu_keys ?? []) === ['request_forms']);
            $division = Role::where('slug', $divisionOf[$slug] ?? '')->first();
            if ($untouched) {
                $role->permissions = array_values(array_unique(array_merge($division?->permissions ?? [], RoleSeeder::RF_FOR_EVERYONE)));
                $role->menu_keys = array_values(array_unique(array_merge($division?->menu_keys ?? [], ['request_forms'])));
            }
            $role->save();
        }
    }

    private function sorted(?array $list): array
    {
        $list = $list ?? [];
        sort($list);

        return $list;
    }

    private function say(string $line): void
    {
        $this->command?->line($line);
    }
}
