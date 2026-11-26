<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Company vehicles, modelled on the Salesforce "Vehicle" object: each one is either
 * Own (bought) or Rent (rented from a supplier). Costs are not typed in here: they
 * come from RF lines that name the vehicle (rent per period, service, other) once
 * their PO is approved.
 */
return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('erp_vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number', 20)->unique();          // Vehicle Id, e.g. B 2601 PFR
            $table->string('ownership', 10);                        // Own / Rent
            $table->unsignedBigInteger('company_id')->nullable();   // master.companies
            $table->string('company_name', 150)->nullable();
            $table->string('kind', 10)->default('Mobil');           // Mobil / Motor
            $table->string('brand', 60)->nullable();
            $table->string('model', 60)->nullable();
            $table->string('vehicle_type', 60)->nullable();
            $table->unsignedSmallInteger('production_year')->nullable();
            $table->string('color', 40)->nullable();
            $table->string('condition', 40)->nullable();            // e.g. Good
            $table->string('usage_status', 40)->nullable();         // e.g. Stand By
            $table->string('region', 80)->nullable();
            $table->string('location', 80)->nullable();
            $table->string('current_area', 80)->nullable();
            $table->unsignedBigInteger('sub_project_id')->nullable()->index();
            $table->string('pic', 120)->nullable();
            $table->string('stnk_name', 150)->nullable();
            $table->string('stnk_address', 500)->nullable();
            $table->string('chassis_number', 60)->nullable();
            $table->string('engine_number', 60)->nullable();
            $table->date('stnk_expiry_date')->nullable();
            $table->date('tax_expiry_date')->nullable();
            $table->date('purchase_date')->nullable();              // Own
            $table->decimal('purchase_price', 18, 2)->nullable();   // Own
            $table->unsignedBigInteger('rental_supplier_id')->nullable()->index(); // Rent
            $table->decimal('rent_price', 18, 2)->nullable();       // Rent, per rent_period
            $table->string('rent_period', 10)->nullable();          // Rent: Bulan / Tahun
            $table->date('rental_start_date')->nullable();          // Rent
            $table->date('rental_end_date')->nullable();            // Rent, as typed in
            $table->string('replacement_plate', 20)->nullable();    // the plate this one replaced
            $table->unsignedInteger('mileage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // An RF line may be for a vehicle: what kind of cost it is, and the rental period it pays.
        Schema::connection('tenant')->table('request_form_items', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->nullable()->index()->after('wid');
            $table->string('vehicle_cost_type', 10)->nullable()->after('vehicle_id'); // Sewa / Servis / Lainnya
            $table->date('rental_start_date')->nullable()->after('vehicle_cost_type');
            $table->date('rental_end_date')->nullable()->after('rental_start_date');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('request_form_items', function (Blueprint $table) {
            $table->dropColumn(['vehicle_id', 'vehicle_cost_type', 'rental_start_date', 'rental_end_date']);
        });
        Schema::connection('tenant')->dropIfExists('erp_vehicles');
    }
};
