<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Advance-Expense Declaration Non Project (Salesforce ADVEXNPJ): money paid to or for an
 * employee (loans, benefits, insurance claims, office events) and how it was used, in one
 * record. Not charged to any WID and no approval. Employees and departments live in the
 * master database, so no foreign keys to them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_employee_advances', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();
            $table->string('department_name', 100)->nullable();
            $table->string('status')->default('Draft');
            $table->string('currency', 10)->default('IDR');

            $table->date('advance_request_date')->nullable();
            $table->decimal('advance_amount', 18, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('expense_type')->nullable();
            $table->string('payment_status')->nullable();
            $table->date('payment_date')->nullable();

            $table->date('declaration_date')->nullable();
            $table->decimal('declaration_amount', 18, 2)->nullable();
            $table->text('declaration_description')->nullable();

            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_employee_advances');
    }
};
