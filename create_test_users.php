<?php
require 'vendor/autoload.php';
require_once 'bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$projectId = session('current_project') ?: 1; // Default to project 1

$usersToCreate = [
    [
        'name' => 'Melvien Welang',
        'email' => 'melvien@example.com',
        'username' => 'melvien',
        'project_role' => 'logistic',
    ],
    [
        'name' => 'Nikmal Hadi',
        'email' => 'nikmal@example.com',
        'username' => 'nikmal',
        'project_role' => 'finance_head',
    ],
    [
        'name' => 'Budi Atasan',
        'email' => 'budi@example.com',
        'username' => 'budi',
        'project_role' => 'manager',
    ],
    [
        'name' => 'Siti Staff',
        'email' => 'siti@example.com',
        'username' => 'siti',
        'project_role' => 'staff',
    ],
];

DB::connection('master')->beginTransaction();
try {
    foreach ($usersToCreate as $data) {
        $user = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ]
        );

        // Assign to projects
        $projects = DB::connection('master')->table('projects')->where('is_active', true)->pluck('id');
        foreach ($projects as $projId) {
            DB::connection('master')->table('user_projects')->updateOrInsert(
                [
                    'user_id' => $user->id,
                    'project_id' => $projId,
                ],
                [
                    'role' => $data['project_role'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        echo "Created/Updated User: {$data['name']} ({$data['project_role']})\n";
    }
    DB::connection('master')->commit();
    echo "Successfully seeded test users.\n";
} catch (\Exception $e) {
    DB::connection('master')->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
