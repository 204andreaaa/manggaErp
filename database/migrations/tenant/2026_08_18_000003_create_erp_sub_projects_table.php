<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('erp_sub_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_parent_id')->constrained('erp_budget_parents')->cascadeOnDelete();
            $table->string('sub_project_code', 50)->unique();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_sub_projects');
    }
};
