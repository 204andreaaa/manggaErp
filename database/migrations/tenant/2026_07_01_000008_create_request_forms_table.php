<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_forms', function (Blueprint $table) {
            $table->id();
            $table->string('rf_no')->unique();
            $table->enum('record_type', ['project', 'non_project']);
            $table->string('project_code')->nullable();
            $table->string('requestor')->nullable();
            $table->string('owner')->nullable();
            $table->string('priority')->default('Normal');
            $table->string('rf_type')->nullable();
            $table->boolean('rf_internal_do')->default(false);
            $table->text('remark')->nullable();
            $table->text('long_remark')->nullable();
            $table->string('recommend_supplier')->nullable();
            $table->date('rf_date')->nullable();
            $table->string('status')->default('Draft');
            $table->bigInteger('total_amount')->default(0);
            $table->string('template_name')->nullable();
            $table->boolean('expense_material_equipment')->default(false);
            $table->boolean('expense_material_subcon')->default(false);
            $table->boolean('expense_transportation')->default(false);
            $table->boolean('expense_personnel')->default(false);
            $table->boolean('expense_office')->default(false);
            $table->boolean('expense_other')->default(false);
            $table->boolean('expense_utilities')->default(false);
            $table->string('rf_for')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['record_type', 'status']);
            $table->index('rf_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_forms');
    }
};
