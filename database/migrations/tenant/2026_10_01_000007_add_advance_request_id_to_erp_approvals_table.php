<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->foreignId('advance_request_id')
                ->nullable()
                ->after('payment_advice_detail_id')
                ->constrained('erp_advance_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('advance_request_id');
        });
    }
};
