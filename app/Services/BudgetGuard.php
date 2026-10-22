<?php

namespace App\Services;

use App\Models\Erp\BudgetPlanDetail;
use App\Models\Erp\ErpWorkItem;
use Illuminate\Validation\ValidationException;

/**
 * Menolak kasbon / deklarasi yang membuat sisa budget minus, baik di
 * Budget Plan Detail (per expense type per bulan) maupun di Remaining WID.
 * Dipanggil saat form disimpan (supaya cepat ketahuan) dan sekali lagi saat
 * approval terakhir (angka yang menentukan, karena budget bisa terpakai
 * dokumen lain selama menunggu approval).
 */
class BudgetGuard
{
    public static function detailShortfall(BudgetPlanDetail $detail, float $amount): ?string
    {
        $remaining = BudgetRollupService::detailRemaining($detail);
        if ($amount <= $remaining + 0.005) {
            return null;
        }

        return "Budget {$detail->expense_type} ({$detail->code}) tidak cukup: sisa Rp ".self::money($remaining)
            .', diminta Rp '.self::money($amount).'.';
    }

    public static function widShortfall(ErpWorkItem $work, float $amount): ?string
    {
        if ($amount <= (float) $work->remaining_budget + 0.005) {
            return null;
        }

        return "Remaining WID {$work->wid_code} tidak cukup: sisa Rp ".self::money($work->remaining_budget)
            .', diminta Rp '.self::money($amount).'.';
    }

    /**
     * @param  array<int, array{detail: BudgetPlanDetail, amount: float}>  $lines
     */
    public static function assertFits(array $lines, ?ErpWorkItem $work, string $field = 'lines'): void
    {
        $errors = [];
        foreach ($lines as $line) {
            if ($msg = self::detailShortfall($line['detail'], (float) $line['amount'])) {
                $errors[] = $msg;
            }
        }
        if ($work && ($msg = self::widShortfall($work, array_sum(array_map(fn ($l) => (float) $l['amount'], $lines))))) {
            $errors[] = $msg;
        }

        if ($errors) {
            throw ValidationException::withMessages([$field => implode(' ', $errors)]);
        }
    }

    public static function money($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}
