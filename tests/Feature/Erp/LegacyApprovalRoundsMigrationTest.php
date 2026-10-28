<?php

namespace Tests\Feature\Erp;

use Illuminate\Support\Facades\DB;
use Tests\ErpTestCase;

class LegacyApprovalRoundsMigrationTest extends ErpTestCase
{
    public function test_legacy_resubmit_after_rejection_is_split_into_a_second_round(): void
    {
        $db = DB::connection('tenant');
        $db->statement('SET FOREIGN_KEY_CHECKS=0');

        // The old PO flow: L1 approved, L2 rejected, L3 cancelled; on resubmit only a new L2 row
        // (and L3) were created and the old approved L1 was reused.
        $row = fn (int $level, string $status, string $created, ?string $acted) => $db->table('erp_approvals')->insertGetId([
            'purchase_order_id' => 777, 'level' => $level, 'round' => 1, 'status' => $status,
            'created_at' => $created, 'updated_at' => $acted ?? $created, 'approved_at' => $acted,
        ]);
        $l1 = $row(1, 'Approved', '2026-10-27 09:00:00', '2026-10-27 09:05:00');
        $l2 = $row(2, 'Rejected', '2026-10-27 09:00:00', '2026-10-27 09:10:00');
        $l3 = $row(3, 'Cancelled', '2026-10-27 09:00:00', '2026-10-27 09:10:00');
        $newL2 = $row(2, 'Approved', '2026-10-27 09:20:00', '2026-10-27 09:21:00');
        $newL3 = $row(3, 'Approved', '2026-10-27 09:20:00', '2026-10-27 09:22:00');

        // An untouched record with no rejection stays as it is.
        $other = $db->table('erp_approvals')->insertGetId(['purchase_order_id' => 778, 'level' => 1, 'round' => 1, 'status' => 'Approved', 'created_at' => '2026-10-27 09:00:00', 'updated_at' => '2026-10-27 09:00:00']);

        (require database_path('migrations/tenant/2026_10_28_000001_split_legacy_approval_history_into_rounds.php'))->up();

        $round = fn (int $id) => (int) $db->table('erp_approvals')->where('id', $id)->value('round');
        $this->assertSame([1, 1, 1], [$round($l1), $round($l2), $round($l3)]);
        $this->assertSame([2, 2], [$round($newL2), $round($newL3)]);
        $this->assertSame(1, $round($other));
    }
}
