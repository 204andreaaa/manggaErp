<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Erp\ErpApprovalConfig;
use App\Models\User;

class ErpApprovalConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // For default testing, let's just pick any active users. 
        // We will query for common names or just pick the first ones.
        $superAdmin = User::whereHas('roles', function($q) { $q->where('name', 'Super Admin'); })->first();
        $admin = User::whereHas('roles', function($q) { $q->where('name', 'Admin'); })->first();
        $finance = User::whereHas('roles', function($q) { $q->where('name', 'Finance'); })->first();
        $procurement = User::whereHas('roles', function($q) { $q->where('name', 'Procurement'); })->first();
        $ceo = User::whereHas('roles', function($q) { $q->where('name', 'CEO'); })->first();

        // Fallbacks if roles aren't assigned cleanly in test env
        $financeId = $finance ? $finance->id : ($superAdmin ? $superAdmin->id : 1);
        $procurementId = $procurement ? $procurement->id : ($admin ? $admin->id : 1);
        $ceoId = $ceo ? $ceo->id : ($superAdmin ? $superAdmin->id : 1);

        // Truncate before seed
        \Illuminate\Support\Facades\DB::connection('tenant')->table('erp_approval_configs')->truncate();

        // 1. Request Form Configs
        ErpApprovalConfig::create([
            'record_type' => 'request_form',
            'level' => 1,
            'name' => 'Procurement Review',
            'user_id' => $procurementId,
        ]);
        
        ErpApprovalConfig::create([
            'record_type' => 'request_form',
            'level' => 2,
            'name' => 'CEO Approval',
            'user_id' => $ceoId,
        ]);

        // 2. Purchase Order Configs
        ErpApprovalConfig::create([
            'record_type' => 'purchase_order',
            'level' => 1,
            'name' => 'Finance Verification',
            'user_id' => $financeId,
        ]);
        
        ErpApprovalConfig::create([
            'record_type' => 'purchase_order',
            'level' => 2,
            'name' => 'Procurement Approval',
            'user_id' => $procurementId,
            'max_amount' => 1000000 
        ]);
        
        ErpApprovalConfig::create([
            'record_type' => 'purchase_order',
            'level' => 3,
            'name' => 'CEO Approval (High Value)',
            'user_id' => $ceoId,
            'min_amount' => 1000000 
        ]);

        // 3. Payment Advice Configs
        ErpApprovalConfig::create([
            'record_type' => 'payment_advice',
            'level' => 1,
            'name' => 'Finance Verification',
            'user_id' => $financeId,
        ]);
        
        ErpApprovalConfig::create([
            'record_type' => 'payment_advice',
            'level' => 2,
            'name' => 'CEO Final Approval',
            'user_id' => $ceoId,
        ]);
    }
}
