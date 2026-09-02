<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.connections.tenant' => array_merge(
    config('database.connections.mysql'),
    ['database' => 'mandau_db']
)]);
DB::purge('tenant');

use Illuminate\Support\Facades\File;

$files = collect(File::files(database_path('migrations/tenant')))
    ->filter(fn ($file) => $file->getExtension() === 'php')
    ->sortBy(fn ($file) => $file->getFilename())
    ->values();

$ran = DB::connection('tenant')->table('migrations')->pluck('migration')->all();

$pending = $files->reject(fn ($file) => in_array(
    pathinfo($file->getFilename(), PATHINFO_FILENAME),
    $ran,
    true
));

echo "Files count: " . $files->count() . "\n";
echo "Ran count: " . count($ran) . "\n";
echo "Pending count: " . $pending->count() . "\n";
foreach ($pending as $p) {
    echo "Pending: " . $p->getFilename() . "\n";
}
