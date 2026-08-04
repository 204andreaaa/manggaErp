<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Payment Terms
        Schema::create('erp_payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Suppliers
        Schema::create('erp_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 50)->unique();
            $table->string('name', 150);
            $table->string('parent_account', 150)->nullable();
            $table->string('classification', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('products_provided', 255)->nullable();
            $table->string('services_provided', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('products', 255)->nullable();
            $table->unsignedBigInteger('payment_terms_id')->nullable()->index();
            $table->string('address', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('website', 150)->nullable();
            $table->text('note')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->timestamps();
        });

        // 3. Contacts
        Schema::create('erp_supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erp_supplier_id')->constrained('erp_suppliers')->cascadeOnDelete();
            $table->string('contact_name', 150);
            $table->string('title', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
        });

        // 4. Attachments
        Schema::create('erp_supplier_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('erp_supplier_id')->constrained('erp_suppliers')->cascadeOnDelete();
            $table->string('type', 50)->default('Attachment');
            $table->string('title', 255);
            $table->string('file_path', 255);
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
        });

        // 5. Warehouses
        Schema::create('erp_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_code', 50)->unique();
            $table->string('name', 150);
            $table->string('type', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->date('last_stock_take_date')->nullable();
            $table->string('work', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('total_value', 16, 2)->default(0);
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_warehouses');
        Schema::dropIfExists('erp_supplier_attachments');
        Schema::dropIfExists('erp_supplier_contacts');
        Schema::dropIfExists('erp_suppliers');
        Schema::dropIfExists('erp_payment_terms');
    }
};
