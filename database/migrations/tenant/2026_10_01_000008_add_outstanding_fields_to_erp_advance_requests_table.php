<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_advance_requests', function (Blueprint $table) {
            // Dihitung: actual_amount yang sudah dibayar - total deklarasi with_advance Approved.
            $table->decimal('advance_outstanding', 20, 2)->default(0)->after('total_amount_advance');
            $table->boolean('is_active')->default(true)->after('advance_outstanding');
        });
    }

    public function down(): void
    {
        Schema::table('erp_advance_requests', function (Blueprint $table) {
            $table->dropColumn(['advance_outstanding', 'is_active']);
        });
    }
};
