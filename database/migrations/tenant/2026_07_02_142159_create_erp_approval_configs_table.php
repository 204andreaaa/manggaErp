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
        Schema::create('erp_approval_configs', function (Blueprint $table) {
            $table->id();
            $table->string('record_type'); // 'project' or 'non_project'
            $table->integer('level');
            $table->string('name')->nullable(); // e.g., 'Step 1', 'Step 2'
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_approval_configs');
    }
};
