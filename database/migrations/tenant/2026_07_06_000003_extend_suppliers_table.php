<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'parent_account')) {
                    $table->string('parent_account', 150)->nullable()->after('name');
                }
                if (!Schema::hasColumn('suppliers', 'classification')) {
                    $table->string('classification', 100)->nullable()->after('parent_account');
                }
                if (!Schema::hasColumn('suppliers', 'industry')) {
                    $table->string('industry', 100)->nullable()->after('classification');
                }
                if (!Schema::hasColumn('suppliers', 'products_provided')) {
                    $table->string('products_provided', 255)->nullable()->after('industry');
                }
                if (!Schema::hasColumn('suppliers', 'services_provided')) {
                    $table->string('services_provided', 255)->nullable()->after('products_provided');
                }
                if (!Schema::hasColumn('suppliers', 'category')) {
                    $table->string('category', 100)->nullable()->after('services_provided');
                }
                if (!Schema::hasColumn('suppliers', 'products')) {
                    $table->string('products', 255)->nullable()->after('category');
                }
                if (!Schema::hasColumn('suppliers', 'payment_terms')) {
                    $table->string('payment_terms', 150)->nullable()->after('products');
                }
                if (!Schema::hasColumn('suppliers', 'fax')) {
                    $table->string('fax', 50)->nullable();
                }
                if (!Schema::hasColumn('suppliers', 'website')) {
                    $table->string('website', 150)->nullable();
                }
            });
        }

        if (!Schema::hasTable('supplier_contacts')) {
            Schema::create('supplier_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('supplier_id');
                $table->string('contact_name', 150);
                $table->string('title', 100)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('phone', 50)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_contacts');

        if (Schema::hasTable('suppliers')) {
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
    }
};
