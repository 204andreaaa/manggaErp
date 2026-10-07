<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Project (IP)" never connected to anything else in the budget hierarchy
        // (unlike Work Item, which ties into Sub Project / Budget Parent for the
        // existing spending-ceiling system) — it was a floating label that only
        // added confusion, so it's removed rather than kept as dead weight.
        Schema::table('erp_advance_requests', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('erp_advance_requests', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('work_id')->constrained('erp_projects');
        });
    }
};
