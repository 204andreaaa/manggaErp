<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::table('request_forms', function (Blueprint $table) {
            $table->boolean('is_project')->default(false)->after('record_type');
            $table->unsignedBigInteger('work_item_id')->nullable()->after('is_project');
            
            // Add foreign key constraint if possible. Sometimes on tenant DBs we use standard foreignId.
            $table->foreign('work_item_id')->references('id')->on('erp_work_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_forms', function (Blueprint $table) {
            $table->dropForeign(['work_item_id']);
            $table->dropColumn(['is_project', 'work_item_id']);
        });
    }
};
