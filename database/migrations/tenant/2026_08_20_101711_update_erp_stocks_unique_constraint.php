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
        Schema::table('erp_stocks', function (Blueprint $table) {
            // Drop foreign key that depends on the unique index
            $table->dropForeign(['erp_product_id']);
            
            // Drop old unique constraint
            $table->dropUnique('erp_stocks_erp_product_id_erp_warehouse_id_unique');
            
            // Add new unique constraint including supplier
            $table->unique(['erp_product_id', 'erp_warehouse_id', 'erp_supplier_id'], 'erp_stocks_prod_wh_sup_unique');
            
            // Re-add foreign key
            $table->foreign('erp_product_id')->references('id')->on('erp_products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_stocks', function (Blueprint $table) {
            $table->dropForeign(['erp_product_id']);
            $table->dropUnique('erp_stocks_prod_wh_sup_unique');
            $table->unique(['erp_product_id', 'erp_warehouse_id'], 'erp_stocks_erp_product_id_erp_warehouse_id_unique');
            $table->foreign('erp_product_id')->references('id')->on('erp_products')->onDelete('cascade');
        });
    }
};
