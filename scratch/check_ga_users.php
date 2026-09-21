<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::with('roles')->get();
echo "All Users and Roles:\n";
foreach ($users as $u) {
    $roles = $u->roles->pluck('name')->implode(', ');
    echo "ID: {$u->id}, Name: {$u->name}, Username: {$u->username}, Email: {$u->email}, Roles: [{$roles}]\n";
}
