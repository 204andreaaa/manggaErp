<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::create('erp_budget_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_parent_id')->constrained('erp_budget_parents')->cascadeOnDelete();
            $table->foreignId('work_item_id')->constrained('erp_work_items')->cascadeOnDelete();
            $table->unsignedBigInteger('po_id')->nullable(); // Can't constrain directly if cross-database, but usually it's tenant DB
            $table->string('type', 50); // DEDUCTION, REVERSION
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_budget_ledgers');
    }
};
