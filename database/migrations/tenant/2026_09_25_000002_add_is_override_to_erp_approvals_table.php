<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->boolean('is_override')->default(false)->after('actual_approver_id');
        });
    }

    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->dropColumn('is_override');
        });
    }
};
