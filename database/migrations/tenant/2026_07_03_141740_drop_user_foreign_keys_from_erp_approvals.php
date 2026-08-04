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
        try {
            Schema::table('erp_approvals', function (Blueprint $table) {
                $table->dropForeign(['assigned_to_user_id']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('erp_approvals', function (Blueprint $table) {
                $table->dropForeign(['actual_approver_id']);
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_approvals', function (Blueprint $table) {
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('actual_approver_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
