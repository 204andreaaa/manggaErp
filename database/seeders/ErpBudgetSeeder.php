<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Erp\ErpBudgetParent;
use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpWorkItem;

class ErpBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Clear old budget data
        \Illuminate\Support\Facades\DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0;');
        ErpWorkItem::truncate();
        ErpSubProject::truncate();
        ErpBudgetParent::truncate();
        \Illuminate\Support\Facades\DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create Budget Parent (Mandau)
        $budget = ErpBudgetParent::create([
            'budget_code'      => 'MANDAU-2026',
            'name'             => 'Mandau',
            'total_budget'     => 1000000000, // 1 Miliar
            'remaining_budget' => 1000000000,
            'status'           => 'Active'
        ]);

        // 2. Create Sub Projects (JSI & Apjatel)
        $spJsi = ErpSubProject::create([
            'budget_parent_id' => $budget->id,
            'sub_project_code' => 'SP-JSI',
            'name'             => 'JSI'
        ]);

        $spApjatel = ErpSubProject::create([
            'budget_parent_id' => $budget->id,
            'sub_project_code' => 'SP-APJ',
            'name'             => 'Apjatel'
        ]);

        // 3. Create Work Items (WID)
        // WID di bawah JSI
        ErpWorkItem::create([
            'sub_project_id'   => $spJsi->id,
            'wid_code'         => 'WID-JSI-001',
            'name'             => 'MS',
            'allocated_budget' => 250000000, // 250 Juta
            'remaining_budget' => 250000000
        ]);

        ErpWorkItem::create([
            'sub_project_id'   => $spJsi->id,
            'wid_code'         => 'WID-JSI-002',
            'name'             => 'Maintenance Jaringan',
            'allocated_budget' => 250000000, // 250 Juta
            'remaining_budget' => 250000000
        ]);

        // WID di bawah Apjatel
        ErpWorkItem::create([
            'sub_project_id'   => $spApjatel->id,
            'wid_code'         => 'WID-APJ-001',
            'name'             => 'Tower',
            'allocated_budget' => 250000000, // 250 Juta
            'remaining_budget' => 250000000
        ]);

        ErpWorkItem::create([
            'sub_project_id'   => $spApjatel->id,
            'wid_code'         => 'WID-APJ-002',
            'name'             => 'Relokasi',
            'allocated_budget' => 250000000, // 250 Juta
            'remaining_budget' => 250000000
        ]);
    }
}
