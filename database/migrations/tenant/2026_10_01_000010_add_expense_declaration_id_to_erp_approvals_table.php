<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->foreignId('expense_declaration_id')
                ->nullable()
                ->after('advance_request_id')
                ->constrained('erp_expense_declarations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_declaration_id');
        });
    }
};
