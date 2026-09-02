<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First truncate the existing configs since they are structurally incompatible
        DB::connection('tenant')->table('erp_approval_configs')->truncate();

        Schema::connection('tenant')->table('erp_approval_configs', function (Blueprint $table) {
            $table->decimal('min_amount', 20, 2)->nullable()->after('name')->comment('Minimum total amount for this rule to apply');
            $table->decimal('max_amount', 20, 2)->nullable()->after('min_amount')->comment('Maximum total amount for this rule to apply');
            $table->boolean('is_project')->nullable()->after('max_amount')->comment('True for Project only, False for Non-Project only, Null for both');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('erp_approval_configs', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount', 'is_project']);
        });
    }
};
