<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settling a kasbon closes it: Finance records the unused money the employee returned,
 * the outstanding drops to 0 and the budget it was holding is released.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('erp_advance_requests', function (Blueprint $table) {
            $table->timestamp('settled_at')->nullable()->after('paid_by_id');
            $table->unsignedBigInteger('settled_by_id')->nullable()->after('settled_at');
            $table->decimal('settlement_amount', 18, 2)->nullable()->after('settled_by_id');
            $table->date('settlement_date')->nullable()->after('settlement_amount');
            $table->string('settlement_note', 500)->nullable()->after('settlement_date');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('erp_advance_requests', function (Blueprint $table) {
            $table->dropColumn(['settled_at', 'settled_by_id', 'settlement_amount', 'settlement_date', 'settlement_note']);
        });
    }
};
