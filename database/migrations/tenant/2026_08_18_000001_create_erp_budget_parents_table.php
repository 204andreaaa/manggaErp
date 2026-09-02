<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('erp_budget_parents', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 50)->unique();
            $table->string('name');
            $table->decimal('total_budget', 20, 2)->default(0);
            $table->decimal('remaining_budget', 20, 2)->default(0);
            $table->string('status', 50)->default('Active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_budget_parents');
    }
};
