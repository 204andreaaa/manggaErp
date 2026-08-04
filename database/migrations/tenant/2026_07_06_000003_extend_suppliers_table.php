<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('parent_account', 150)->nullable()->after('name');
            $table->string('classification', 100)->nullable()->after('parent_account');
            $table->string('industry', 100)->nullable()->after('classification');
            $table->string('products_provided', 255)->nullable()->after('industry');
            $table->string('services_provided', 255)->nullable()->after('products_provided');
            $table->string('category', 100)->nullable()->after('services_provided');
            $table->string('products', 255)->nullable()->after('category');
            $table->string('payment_terms', 150)->nullable()->after('products');
            $table->string('fax', 50)->nullable()->after('phone');
            $table->string('website', 150)->nullable()->after('fax');
        });

        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('contact_name', 150);
            $table->string('title', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn([
                'parent_account',
                'classification',
                'industry',
                'products_provided',
                'services_provided',
                'category',
                'products',
                'payment_terms',
                'fax',
                'website'
            ]);
        });
    }
};
