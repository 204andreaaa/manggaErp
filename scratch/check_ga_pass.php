<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('username', 'ga_budi')->orWhere('email', 'ga@local')->first();
if ($user) {
    echo "Found GA User: {$user->name} ({$user->username} / {$user->email})\n";
    foreach (['password', 'password123', 'admin123', '123456', '12345678'] as $pass) {
        if (Illuminate\Support\Facades\Hash::check($pass, $user->password)) {
            echo "Password matches: {$pass}\n";
            break;
        }
    }
}
