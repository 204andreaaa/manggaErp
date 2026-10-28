<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Before approval rounds existed, a resubmit after a rejection either reused the
 * earlier approved rows (POs) or added new rows to the same list, so the
 * previous migration put everything in round 1 and the history reads as one
 * mixed list. Split it: a row created after a rejection in the current round
 * opens the next round. Only touches records that still have a single round.
 */
return new class extends Migration
{
    private const PARENTS = [
        'request_form_id', 'purchase_order_id', 'payment_advice_id',
        'payment_advice_detail_id', 'advance_request_id', 'expense_declaration_id',
    ];

    public function up(): void
    {
        $db = DB::connection('tenant');

        foreach (self::PARENTS as $column) {
            $parents = $db->table('erp_approvals')
                ->whereNotNull($column)
                ->groupBy($column)
                ->havingRaw('MAX(round) = 1')
                ->havingRaw("SUM(status = 'Rejected') > 0")
                ->pluck($column);

            foreach ($parents as $parentId) {
                $rows = $db->table('erp_approvals')->where($column, $parentId)
                    ->orderBy('created_at')->orderBy('id')
                    ->get(['id', 'status', 'created_at', 'approved_at', 'updated_at']);

                $round = 1;
                $rejected = null; // [time, id] of the rejection that closed the current round
                foreach ($rows as $row) {
                    $afterRejection = $rejected !== null
                        && ($row->created_at > $rejected[0] || ($row->created_at == $rejected[0] && $row->id > $rejected[1]));
                    if ($afterRejection) {
                        $round++;
                        $rejected = null;
                    }
                    if ($round > 1) {
                        $db->table('erp_approvals')->where('id', $row->id)->update(['round' => $round]);
                    }
                    if ($row->status === 'Rejected') {
                        $rejected = [$row->approved_at ?? $row->updated_at, $row->id];
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Rounds can't be told apart again once merged; nothing to undo safely.
    }
};
