<?php

namespace Tests\Feature\Erp;

use App\Models\Company;
use App\Models\Erp\RequestForm;
use App\Models\Erp\Vehicle;
use App\Models\User;
use Tests\Feature\Erp\PurchaseOrder\PurchaseOrderTestCase;

/**
 * Vehicles (Own / Rent): the register itself, the plate on an RF line, and the costs
 * and rent end date that follow from approved POs.
 */
class VehicleTest extends PurchaseOrderTestCase
{
    private User $ga;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ga = $this->makeUser('general_affair');
        $this->company = Company::firstOrCreate(['code' => 'MANDAU'], ['name' => 'Mandau', 'short_name' => 'MANDAU', 'is_active' => true]);
    }

    private function itemPayload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Sewa mobil bulanan', 'wid' => 'NON-PROJECT', 'currency' => 'IDR', 'unit_cost' => 3_000_000,
            'qty' => 1, 'status' => 'Requested',
        ], $overrides);
    }

    private function rfPayload(array $items, int $departmentId): array
    {
        return ['record_type' => 'non_project', 'priority' => 'Normal', 'rf_date' => now()->toDateString(),
            'expense_transportation' => 1, 'department_id' => $departmentId, 'items' => $items];
    }

    private function ownPayload(array $overrides = []): array
    {
        return array_merge([
            'ownership' => 'Own', 'plate_number' => 'b 2363 sox', 'kind' => 'Mobil', 'production_year' => 2017,
            'purchase_price' => 250_000_000, 'purchase_date' => '2017-07-17', 'is_active' => 1,
        ], $overrides);
    }

    private function rentPayload(array $overrides = []): array
    {
        return array_merge([
            'ownership' => 'Rent', 'plate_number' => 'B 2601 PFR', 'company_id' => $this->company->id, 'kind' => 'Mobil',
            'brand' => 'Daihatsu', 'model' => 'Grand Max', 'production_year' => 2012, 'condition' => 'Good',
            'region' => 'Kalimantan Timur', 'location' => 'Balikpapan', 'current_area' => 'Kalimantan Timur',
            'stnk_expiry_date' => now()->addYear()->toDateString(), 'rental_supplier_id' => $this->supplier->id,
            'rent_price' => 3_000_000, 'rent_period' => 'Bulan', 'rental_start_date' => now()->subMonths(2)->toDateString(),
            'rental_end_date' => now()->subDay()->toDateString(), 'is_active' => 1,
        ], $overrides);
    }

    private function makeVehicle(string $ownership = 'Rent', array $overrides = []): Vehicle
    {
        $this->actingInProject($this->ga)
            ->post(route('erp.vehicles.store'), $ownership === 'Rent' ? $this->rentPayload($overrides) : $this->ownPayload($overrides))
            ->assertSessionHasNoErrors();

        return Vehicle::latest('id')->firstOrFail();
    }

    public function test_ga_registers_own_and_rent_vehicles_with_the_salesforce_required_fields(): void
    {
        $this->actingInProject($this->ga)->get(route('erp.vehicles.create'))->assertOk()->assertSee('Milik Sendiri (Own)');

        // a rented car needs its vendor, rent and period; an own car its purchase price
        $this->post(route('erp.vehicles.store'), ['ownership' => 'Rent', 'plate_number' => 'B 1 X', 'kind' => 'Mobil', 'production_year' => 2020])
            ->assertSessionHasErrors(['rental_supplier_id', 'rent_price', 'rent_period', 'rental_start_date', 'rental_end_date', 'company_id', 'location', 'stnk_expiry_date']);
        $this->post(route('erp.vehicles.store'), $this->ownPayload(['purchase_price' => null]))->assertSessionHasErrors('purchase_price');

        $own = $this->makeVehicle('Own');
        $this->assertSame('B 2363 SOX', $own->plate_number);
        $this->assertNull($own->rental_supplier_id);

        // the same plate twice is refused
        $this->post(route('erp.vehicles.store'), $this->ownPayload(['plate_number' => 'B 2363 SOX']))->assertSessionHasErrors('plate_number');

        $rent = $this->makeVehicle('Rent');
        $this->assertSame('MANDAU', $rent->company_name);
        $this->assertNull($rent->purchase_price);
        $this->get(route('erp.vehicles.show', $rent))->assertSee('Rp 3.000.000 / bulan');

        // a yearly rent
        $yearly = $this->makeVehicle('Rent', ['plate_number' => 'B 2361 SOW', 'rent_price' => 36_000_000, 'rent_period' => 'Tahun']);
        $this->get(route('erp.vehicles.show', $yearly))->assertSee('Rp 36.000.000 / tahun');
        $this->post(route('erp.vehicles.store'), $this->rentPayload(['plate_number' => 'B 7 Q', 'rent_period' => 'Minggu']))->assertSessionHasErrors('rent_period');

        // its rent ended yesterday: Expired
        $this->get(route('erp.vehicles.index'))->assertOk()->assertSee('B 2601 PFR')->assertSee('Expired');
        $this->get(route('erp.vehicles.show', $rent))->assertOk()->assertSee('Masa Sewa')->assertSee('Expired');
    }

    public function test_only_people_with_the_vehicle_permissions_can_open_or_change_vehicles(): void
    {
        $vehicle = $this->makeVehicle('Own');
        $staff = $this->makeUser('staff');

        $this->actingInProject($staff)->get(route('erp.vehicles.index'))->assertForbidden();
        $this->post(route('erp.vehicles.store'), $this->ownPayload(['plate_number' => 'B 9 Z']))->assertForbidden();

        $ceo = $this->makeUser('ceo');
        $this->actingInProject($ceo)->get(route('erp.vehicles.show', $vehicle))->assertOk()->assertDontSee(route('erp.vehicles.edit', $vehicle));
        $this->put(route('erp.vehicles.update', $vehicle), $this->ownPayload())->assertForbidden();
    }

    public function test_an_rf_line_can_name_a_vehicle_with_its_cost_type_and_a_rent_needs_its_period(): void
    {
        $rent = $this->makeVehicle('Rent');
        $own = $this->makeVehicle('Own');
        $requester = $this->makeUser('general_affair');
        $department = $this->giveDepartment($requester);
        $post = fn (array $item) => $this->actingInProject($requester)->post(route('erp.request-form.store'),
            $this->rfPayload([$this->itemPayload($item)], $department->id));

        $post(['vehicle_id' => $rent->id])->assertSessionHasErrors('items.0.vehicle_cost_type');
        $post(['vehicle_id' => $rent->id, 'vehicle_cost_type' => 'Sewa'])->assertSessionHasErrors(['items.0.rental_start_date', 'items.0.rental_end_date']);
        $post(['vehicle_id' => $own->id, 'vehicle_cost_type' => 'Sewa', 'rental_start_date' => '2026-12-01', 'rental_end_date' => '2026-12-31'])
            ->assertSessionHasErrors('items.0.vehicle_cost_type');

        $post(['vehicle_id' => $rent->id, 'vehicle_cost_type' => 'Sewa', 'rental_start_date' => '2026-12-01', 'rental_end_date' => '2027-05-31'])
            ->assertSessionHasNoErrors();
        $item = RequestForm::latest('id')->first()->items()->sole();
        $this->assertSame($rent->id, (int) $item->vehicle_id);
        $this->assertSame('2027-05-31', $item->rental_end_date->toDateString());
        $this->get(route('erp.request-form.edit', $item->requestForm))->assertOk()->assertSee('Kendaraan (Plat)')->assertSee('"rental_end_date":"2027-05-31"', false);
        $this->get(route('erp.request-form.create', ['type' => 'non_project']))->assertOk()->assertSee('B 2601 PFR');

        // a service line keeps no rent period; a line without a vehicle keeps nothing
        $post(['vehicle_id' => $own->id, 'vehicle_cost_type' => 'Servis', 'rental_start_date' => '2026-12-01', 'rental_end_date' => '2026-12-31'])->assertSessionHasNoErrors();
        $service = RequestForm::latest('id')->first()->items()->sole();
        $this->assertSame('Servis', $service->vehicle_cost_type);
        $this->assertNull($service->rental_start_date);

        $post(['vehicle_cost_type' => 'Servis'])->assertSessionHasNoErrors();
        $this->assertNull(RequestForm::latest('id')->first()->items()->sole()->vehicle_cost_type);

        $this->get(route('erp.request-form.show', RequestForm::latest('id')->skip(1)->first()))->assertOk()->assertSee($own->plate_number);
    }

    public function test_approved_po_lines_become_the_vehicle_costs_and_a_paid_rent_extends_the_rent(): void
    {
        $vehicle = $this->makeVehicle('Rent');
        $this->assertSame('Expired', $vehicle->rentStatus());

        $rf = $this->makeApprovedRf([
            ['name' => 'Sewa mobil bulanan', 'qty' => 6, 'unit_cost' => 3_000_000],
            ['name' => 'Servis berkala', 'qty' => 1, 'unit_cost' => 750_000],
        ], ['record_type' => 'non_project', 'is_project' => false, 'project_code' => null]);
        $rf->items[0]->update(['vehicle_id' => $vehicle->id, 'vehicle_cost_type' => 'Sewa',
            'rental_start_date' => now()->toDateString(), 'rental_end_date' => now()->addMonths(6)->toDateString()]);
        $rf->items[1]->update(['vehicle_id' => $vehicle->id, 'vehicle_cost_type' => 'Servis']);

        // an RF alone is not a cost yet
        $this->actingInProject($this->ga)->get(route('erp.vehicles.show', $vehicle))->assertOk()
            ->assertSee('Pengajuan Dalam Proses')->assertSee($rf->rf_no)->assertSee('Belum ada biaya');
        $this->assertSame('Expired', $vehicle->rentStatus());

        $po = $this->makeApprovedPo($rf->fresh('items'));

        $vehicle->refresh();
        $this->assertSame(now()->addMonths(6)->toDateString(), $vehicle->effectiveRentalEndDate()->toDateString());
        $this->assertSame('Not Yet', $vehicle->rentStatus());
        $this->get(route('erp.vehicles.show', $vehicle))->assertOk()
            ->assertSee($po->po_no)->assertSee('Rp 18.000.000')->assertSee('Rp 750.000')->assertSee('Rp 18.750.000');
        $this->get(route('erp.purchase-orders.show', $po))->assertOk()->assertSee('B 2601 PFR');

        // a cancelled PO stops counting, and the rent is expired again
        $po->update(['status' => 'Cancelled']);
        $this->assertSame('Expired', $vehicle->fresh()->rentStatus());
        $this->assertTrue($vehicle->costLines()->isEmpty());
    }

    public function test_a_vehicle_used_on_an_rf_cannot_be_deleted(): void
    {
        $vehicle = $this->makeVehicle('Own');
        $rf = $this->makeApprovedRf([['name' => 'Servis', 'qty' => 1, 'unit_cost' => 500_000]]);
        $rf->items[0]->update(['vehicle_id' => $vehicle->id, 'vehicle_cost_type' => 'Servis']);

        $this->actingInProject($this->ga)->delete(route('erp.vehicles.destroy', $vehicle))->assertSessionHas('error');
        $this->assertNotSoftDeleted($vehicle);

        $unused = $this->makeVehicle('Own', ['plate_number' => 'B 512 BFJ']);
        $this->delete(route('erp.vehicles.destroy', $unused))->assertRedirect(route('erp.vehicles.index'));
        $this->assertSoftDeleted($unused);
    }
}
