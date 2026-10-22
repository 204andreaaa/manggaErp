<?php

namespace App\Services;

use App\Models\Erp\BudgetPlanDetail;
use Illuminate\Validation\ValidationException;

/**
 * Menolak kasbon / deklarasi yang membuat sisa Budget Plan Detail minus.
 * (WID tidak dicek di sini: jatahnya sudah keluar dari WID waktu Annual
 * Budget Plan dibuat.) Dipanggil saat form disimpan supaya cepat ketahuan,
 * dan sekali lagi saat approval terakhir — angka yang menentukan, karena
 * budget bisa terpakai dokumen lain selama menunggu approval.
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

    /**
     * @param  array<int, array{detail: BudgetPlanDetail, amount: float}>  $lines
     */
    public static function assertFits(array $lines, string $field = 'lines'): void
    {
        $errors = [];
        foreach ($lines as $line) {
            if ($msg = self::detailShortfall($line['detail'], (float) $line['amount'])) {
                $errors[] = $msg;
            }
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
