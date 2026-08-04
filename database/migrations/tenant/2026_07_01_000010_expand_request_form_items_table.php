<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_form_items', function (Blueprint $table) {
            $table->string('currency')->default('IDR')->after('product_description');
            $table->bigInteger('original_total_cost')->default(0)->after('currency');
            $table->bigInteger('actual_cost')->default(0)->after('original_total_cost');
            $table->decimal('qty_fulfilled', 15, 2)->default(0)->after('qty');
            $table->date('date_required')->nullable()->after('qty_fulfilled');
            $table->string('pic')->nullable()->after('date_required');
            $table->boolean('within_budget')->default(false)->after('pic');
        });
    }

    public function down(): void
    {
        Schema::table('request_form_items', function (Blueprint $table) {
            $table->dropColumn([
                'currency',
                'original_total_cost',
                'actual_cost',
                'qty_fulfilled',
                'date_required',
                'pic',
                'within_budget',
            ]);
        });
    }
};
