<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Company-wide settings an admin can change from the app (key / value).
 * Starts with the default tax rates of customer invoices.
 */
return new class extends Migration
{
    protected $connection = 'master';

    public function up(): void
    {
        if (!Schema::connection('master')->hasTable('app_settings')) {
            Schema::connection('master')->create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        foreach (['invoice_ppn_rate' => '11', 'invoice_pph_rate' => '2'] as $key => $value) {
            DB::connection('master')->table('app_settings')->insertOrIgnore([
                'key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection('master')->dropIfExists('app_settings');
    }
};
