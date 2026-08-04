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
        Schema::create('erp_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('pr_no')->unique();
            $table->foreignId('request_form_id')->constrained('request_forms')->cascadeOnDelete();
            $table->string('requestor')->nullable();
            $table->date('pr_date');
            $table->string('status')->default('Draft');
            
            // Flags for expense types (copied from RF or selected during PR creation)
            $table->boolean('expense_material_equipment')->default(false);
            $table->boolean('expense_material_subcon')->default(false);
            $table->boolean('expense_transportation')->default(false);
            $table->boolean('expense_personnel')->default(false);
            $table->boolean('expense_office')->default(false);
            $table->boolean('expense_other')->default(false);
            $table->boolean('expense_utilities')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_purchase_requests');
    }
};
