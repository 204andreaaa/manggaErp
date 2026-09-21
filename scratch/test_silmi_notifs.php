<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('username', 'silmi')->orWhere('email', 'silmi@local.com')->first();
if ($user) {
    auth()->login($user);
    
    // Temporarily test Draft status on PO 2
    $po = App\Models\Erp\ErpPurchaseOrder::find(2);
    $origStatus = $po->status;
    $po->status = 'Draft';
    $po->save();
    
    $controller = new App\Http\Controllers\NotificationController();
    $res = $controller->index(request());
    echo "\nNotifications for Silmi when PO 2 is Draft & Verified:\n";
    echo json_encode($res->getData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    // Restore
    $po->status = $origStatus;
    $po->save();
}
