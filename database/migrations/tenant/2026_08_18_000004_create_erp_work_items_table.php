<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('erp_work_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_project_id')->constrained('erp_sub_projects')->cascadeOnDelete();
            $table->string('wid_code', 50)->unique();
            $table->string('name');
            $table->decimal('allocated_budget', 20, 2)->default(0);
            $table->decimal('remaining_budget', 20, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_work_items');
    }
};
