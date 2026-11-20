<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Taxes on customer invoices. invoice_amount stays the value before tax (DPP).
 *  - PPN is added on top: total_amount = DPP + PPN;
 *  - PPh is withheld by the customer on the DPP and counts as paid at once:
 *    amount_due = total_amount - PPh.
 * Existing invoices get 0% so their amounts do not change until Finance sets the rates.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('erp_invoices', function (Blueprint $table) {
            $table->decimal('ppn_rate', 5, 2)->default(0)->after('invoice_amount_usd');
            $table->decimal('ppn_amount', 18, 2)->default(0)->after('ppn_rate');
            $table->decimal('total_amount', 18, 2)->default(0)->after('ppn_amount');
            $table->decimal('pph_rate', 5, 2)->default(0)->after('total_amount');
            $table->decimal('pph_amount', 18, 2)->default(0)->after('pph_rate');
            $table->decimal('amount_due', 18, 2)->default(0)->after('pph_amount');
        });

        DB::connection('tenant')->table('erp_invoices')->update([
            'total_amount' => DB::raw('invoice_amount'),
            'amount_due' => DB::raw('invoice_amount'),
        ]);
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('erp_invoices', function (Blueprint $table) {
            $table->dropColumn(['ppn_rate', 'ppn_amount', 'total_amount', 'pph_rate', 'pph_amount', 'amount_due']);
        });
    }
};
