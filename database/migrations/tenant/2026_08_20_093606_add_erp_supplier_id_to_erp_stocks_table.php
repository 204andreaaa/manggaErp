<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('erp_stocks', function (Blueprint $table) {
            $table->foreignId('erp_supplier_id')->nullable()->constrained('erp_suppliers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_stocks', function (Blueprint $table) {
            $table->dropForeign(['erp_supplier_id']);
            $table->dropColumn('erp_supplier_id');
        });
    }
};
