<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Employee departments that aren't on the master list ("Umum", the old combined
 * "Procurement & GA", or empty) are re-guessed from the user's roles, so Procurement
 * and GA land in their own departments. Users without an employee record get one the
 * way saving the user does. Anyone no role points to is left for an admin to fill in.
 */
return new class extends Migration
{
    public function up(): void
    {
        $valid = Department::where('status', 'active')->pluck('name')->all();

        foreach (User::with('employee')->get() as $user) {
            $current = $user->employee?->department;
            if ($current && in_array($current, $valid, true)) {
                continue;
            }

            $department = Department::guessFromRoles($user);
            if (! $department) {
                continue;
            }

            if ($user->employee) {
                $user->employee->update(['department' => $department->name]);
            } else {
                Employee::create([
                    'user_id' => $user->id,
                    'nik' => sprintf('EMP-%04d', $user->id),
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'position' => $user->position ?? 'Staff',
                    'department' => $department->name,
                    'signature_path' => $user->signature_path,
                    'employment_status' => 'permanent',
                    'join_date' => now()->toDateString(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data fix: the old values were wrong, nothing to restore.
    }
};
