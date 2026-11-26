<?php

namespace Database\Seeders;

use App\Models\Erp\ErpSubProject;
use App\Models\Erp\ErpSupplier;
use App\Models\Erp\Vehicle;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Example vehicles so the Kendaraan pages are not empty: rented and own cars and motorbikes,
 * some with STNK / pajak already expired or due soon so the alerts show. Dates are relative
 * to today. Safe to run again: vehicles are matched on the plate, suppliers on the code.
 *
 *   php artisan db:seed --class="Database\Seeders\ErpVehicleSeeder"
 */
class ErpVehicleSeeder extends Seeder
{
    private const NOTE = 'Data contoh (ErpVehicleSeeder).';

    public function run(): void
    {
        $today = Carbon::today();
        $d = fn (int $days) => $today->copy()->addDays($days)->toDateString();

        $suppliers = [
            'rent1' => ErpSupplier::firstOrCreate(['supplier_code' => 'SUP-RENT-DEMO1'], [
                'name' => 'CV Sewa Kendaraan Contoh', 'category' => 'Rental Kendaraan', 'phone' => '021-0000001', 'address' => 'Jakarta',
            ]),
            'rent2' => ErpSupplier::firstOrCreate(['supplier_code' => 'SUP-RENT-DEMO2'], [
                'name' => 'PT Armada Rental Contoh', 'category' => 'Rental Kendaraan', 'phone' => '021-0000002', 'address' => 'Bekasi',
            ]),
        ];
        // own vehicles are registered to the PT of the project (tenant) being seeded
        $owner = Project::where('db_name', config('database.connections.tenant.database'))->first()?->letterheadName();
        $subProjects = ErpSubProject::orderBy('id')->pluck('id')->all();
        $sub = fn (int $i) => $subProjects ? $subProjects[$i % count($subProjects)] : null;

        $rent = fn (string $supplier, float $price, string $period, int $startDays, int $endDays) => [
            'ownership' => 'Rent', 'rental_supplier_id' => $suppliers[$supplier]->id, 'rent_price' => $price, 'rent_period' => $period,
            'rental_start_date' => $d($startDays), 'rental_end_date' => $d($endDays), 'stnk_name' => $suppliers[$supplier]->name,
        ];
        $own = fn (float $price, int $boughtDays) => [
            'ownership' => 'Own', 'purchase_price' => $price, 'purchase_date' => $d($boughtDays), 'stnk_name' => $owner,
        ];

        $vehicles = [
            ['B 2601 PFR', $rent('rent1', 7_500_000, 'Bulan', -200, 165), ['Mobil', 'Toyota', 'Avanza', 'MPV', 2023, 'Putih', 'Good', 'In Use', 'Jabodetabek', 'Jakarta Selatan', 'Kantor Pusat', 'Andi', 300, 330, 42_150]],
            ['B 1824 TKL', $rent('rent1', 8_200_000, 'Bulan', -340, 25), ['Mobil', 'Toyota', 'Innova Zenix', 'MPV', 2024, 'Hitam', 'Good', 'In Use', 'Jabodetabek', 'Jakarta Pusat', 'Kantor Pusat', 'Budi', 20, 20, 18_900]],
            ['B 9932 KZA', $rent('rent2', 85_000_000, 'Tahun', -380, -15), ['Mobil', 'Mitsubishi', 'Xpander', 'MPV', 2022, 'Silver', 'Good', 'Stand By', 'Jabodetabek', 'Bekasi', 'Gudang Bekasi', 'Citra', 120, 120, 61_400]],
            ['D 1450 ABK', $rent('rent2', 96_000_000, 'Tahun', -60, 305), ['Mobil', 'Daihatsu', 'Gran Max', 'Pick Up', 2023, 'Putih', 'Good', 'In Use', 'Jawa Barat', 'Bandung', 'Site Bandung', 'Dedi', 15, 200, 37_800]],
            ['L 1877 NM', $rent('rent1', 6_900_000, 'Bulan', -90, 90), ['Mobil', 'Suzuki', 'Ertiga', 'MPV', 2023, 'Abu-abu', 'Good', 'In Use', 'Jawa Timur', 'Surabaya', 'Site Surabaya', 'Eko', -10, 250, 29_350]],
            ['B 3310 SJP', $rent('rent2', 9_500_000, 'Bulan', -150, 10), ['Mobil', 'Toyota', 'Hilux', 'Double Cabin', 2022, 'Putih', 'Need Service', 'Stand By', 'Jabodetabek', 'Tangerang', 'Site Tangerang', 'Fajar', 60, -5, 88_200]],
            ['B 4471 FXY', $rent('rent1', 1_250_000, 'Bulan', -30, 335), ['Motor', 'Honda', 'Vario 160', 'Matic', 2024, 'Merah', 'Good', 'In Use', 'Jabodetabek', 'Jakarta Barat', 'Kantor Pusat', 'Gilang', 280, 280, 6_100]],
            ['B 1120 MDU', $own(325_000_000, -900), ['Mobil', 'Toyota', 'Fortuner', 'SUV', 2021, 'Hitam', 'Good', 'In Use', 'Jabodetabek', 'Jakarta Selatan', 'Kantor Pusat', 'Hendra', 45, 45, 74_500]],
            ['B 8765 MDU', $own(245_000_000, -1400), ['Mobil', 'Toyota', 'Kijang Innova', 'MPV', 2019, 'Silver', 'Good', 'Stand By', 'Jabodetabek', 'Jakarta Selatan', 'Kantor Pusat', 'Indra', -40, -40, 132_700]],
            ['F 1530 MDU', $own(205_000_000, -700), ['Mobil', 'Mitsubishi', 'L300', 'Pick Up', 2022, 'Hitam', 'Need Service', 'In Use', 'Jawa Barat', 'Bogor', 'Gudang Bogor', 'Joko', 150, 150, 55_900]],
            ['B 6021 MDU', $own(28_500_000, -500), ['Motor', 'Yamaha', 'NMAX', 'Matic', 2023, 'Biru', 'Good', 'In Use', 'Jabodetabek', 'Depok', 'Site Depok', 'Kurnia', 7, 210, 21_300]],
            ['B 9001 MDU', $own(310_000_000, -2200), ['Mobil', 'Honda', 'CR-V', 'SUV', 2018, 'Putih', 'Broken', 'Not Used', 'Jabodetabek', 'Jakarta Selatan', 'Kantor Pusat', null, 400, 400, 168_000], false],
        ];

        foreach ($vehicles as $i => $row) {
            [$plate, $ownership, $info] = $row;
            [$kind, $brand, $model, $type, $year, $color, $condition, $usage, $region, $location, $area, $pic, $stnkDays, $taxDays, $km] = $info;

            $vehicle = Vehicle::withTrashed()->firstOrNew(['plate_number' => $plate]);
            $vehicle->fill(array_merge([
                'kind' => $kind, 'brand' => $brand, 'model' => $model, 'vehicle_type' => $type, 'production_year' => $year,
                'color' => $color, 'condition' => $condition, 'usage_status' => $usage, 'region' => $region,
                'location' => $location, 'current_area' => $area, 'sub_project_id' => $sub($i), 'pic' => $pic,
                'stnk_expiry_date' => $d($stnkDays), 'tax_expiry_date' => $d($taxDays), 'mileage' => $km,
                'chassis_number' => 'MHF' . str_pad((string) (11000 + $i * 37), 14, '0', STR_PAD_LEFT),
                'engine_number' => 'ENG' . str_pad((string) (52000 + $i * 41), 9, '0', STR_PAD_LEFT),
                'is_active' => $row[3] ?? true, 'description' => self::NOTE,
            ], $ownership));
            $vehicle->deleted_at = null;
            $vehicle->save();
        }

        $this->command?->info(count($vehicles) . ' kendaraan contoh siap.');
    }
}
