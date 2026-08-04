<?php
use App\Services\TenantManager;
$projects = DB::connection('master')->table('projects')->get();
foreach ($projects as $project) {
    try {
        echo "Switching to project: {$project->name} ({$project->id})\n";
        TenantManager::switchToProject($project->id, false); 
        
        $res = DB::table('request_form_items')
            ->where('status', 'Ordered')
            ->update(['status' => 'Completed']);
            
        echo "Updated $res Ordered items in request_form_items for {$project->name}\n";
    } catch (\Exception $e) {
        echo "Failed for {$project->name}: " . $e->getMessage() . "\n";
    }
}
echo "Done\n";
