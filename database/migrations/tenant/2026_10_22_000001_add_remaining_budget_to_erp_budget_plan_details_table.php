<?php

use App\Services\BudgetRollupService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Budget sekarang terpotong saat kasbon Approved (bukan saat deklarasi).
 * Selain menambah kolom sisa budget, migration ini memotong Remaining WID
 * untuk kasbon dan deklarasi without advance yang sudah Approved sebelumnya,
 * karena dulu keduanya tidak pernah menyentuh WID.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_budget_plan_details', function (Blueprint $table) {
            $table->decimal('remaining_budget', 20, 2)->default(0)->after('actual_expense_amount');
        });

        $db = DB::connection('tenant');

        $used = $db->table('erp_advance_requests')
            ->whereNull('deleted_at')->where('status', 'Approved')
            ->selectRaw('work_id, SUM(actual_amount) as total')->groupBy('work_id')
            ->pluck('total', 'work_id')->all();

        $withoutAdvance = $db->table('erp_expense_declarations')
            ->whereNull('deleted_at')->where('status', 'Approved')->where('record_type', 'without_advance')
            ->selectRaw('work_id, SUM(converted_amount_idr) as total')->groupBy('work_id')
            ->pluck('total', 'work_id')->all();

        foreach ($withoutAdvance as $workId => $total) {
            $used[$workId] = ($used[$workId] ?? 0) + $total;
        }

        foreach ($used as $workId => $total) {
            $db->table('erp_work_items')->where('id', $workId)
                ->update(['remaining_budget' => DB::raw('remaining_budget - '.(float) $total)]);
        }

        foreach ($db->table('erp_advance_requests')->whereNull('deleted_at')->pluck('id') as $id) {
            BudgetRollupService::recalculateAdvanceRequest($id);
        }
        foreach ($db->table('erp_budget_plan_details')->whereNull('deleted_at')->pluck('id') as $id) {
            BudgetRollupService::recalculateDetail($id);
        }
    }

    public function down(): void
    {
        Schema::table('erp_budget_plan_details', function (Blueprint $table) {
            $table->dropColumn('remaining_budget');
        });
    }
};
