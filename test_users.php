<?php
require 'vendor/autoload.php';
require_once 'bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// simulate projectId = 1
$projectId = 1;
$users = \App\Models\User::select('users.*', 'user_projects.role as project_role')
    ->join('user_projects', 'users.id', '=', 'user_projects.user_id')
    ->where('user_projects.project_id', $projectId)
    ->orderBy('users.name')
    ->get();

echo "Count users in project 1: " . $users->count() . "\n";
if ($users->count() > 0) {
    echo "First user: " . $users->first()->name . "\n";
}
