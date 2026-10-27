<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Approval rounds (Salesforce-style history): every submit creates a new round of
 * approval rows; earlier rounds are kept untouched. Existing rows become round 1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->unsignedInteger('round')->default(1)->after('level')->index();
            $table->unsignedBigInteger('submitted_by_id')->nullable()->after('round');
        });

        DB::table('erp_approvals')->whereNull('round')->orWhere('round', 0)->update(['round' => 1]);
    }

    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropIndex(['round']);
            $table->dropColumn(['round', 'submitted_by_id']);
        });
    }
};
