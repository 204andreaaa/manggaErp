<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Erp\ErpWarehouse;
use Tests\ErpTestCase;

class WarehouseTest extends ErpTestCase
{
    use MasterDataHelpers;

    private function warehouse(array $attributes = []): ErpWarehouse
    {
        return ErpWarehouse::create(array_merge([
            'warehouse_code' => 'WH'.random_int(100, 999),
            'name' => 'Gudang Utama',
            'is_active' => true,
        ], $attributes));
    }

    public function test_list_and_detail_pages_open_and_datatable_searches_and_orders(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $main = $this->warehouse(['warehouse_code' => 'WH001', 'name' => 'Gudang Utama', 'type' => 'Central']);
        $this->warehouse(['warehouse_code' => 'WH002', 'name' => 'Site Bekasi', 'type' => 'Site']);

        $this->get(route('erp.warehouses.index'))->assertOk();
        $this->get(route('erp.warehouses.show', $main))->assertOk()->assertSee('Gudang Utama');

        $res = $this->datatable('erp.warehouses.datatable', '', 1, 'desc', 'get')->assertOk();
        $res->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 2]);
        $this->assertSame(['WH002', 'WH001'], array_column($res->json('data'), 'warehouse_code'));

        $search = $this->datatable('erp.warehouses.datatable', 'site', 0, 'asc', 'get');
        $search->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1]);
        $this->assertSame('WH002', $search->json('data.0.warehouse_code'));
    }

    public function test_create_generates_code_and_rejects_duplicates_and_bad_input(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        $this->postJson(route('erp.warehouses.store'), ['name' => 'Gudang Utama', 'capacity' => 500, 'is_active' => 'on'])
            ->assertOk()->assertJsonStructure(['success']);
        $this->assertDatabaseHas('erp_warehouses', ['warehouse_code' => 'WH001', 'name' => 'Gudang Utama', 'capacity' => 500, 'is_active' => 1], 'tenant');

        // An unticked "active" checkbox is simply absent from the form.
        $this->postJson(route('erp.warehouses.store'), ['warehouse_code' => 'wh-b', 'name' => 'Inactive one'])->assertOk();
        $this->assertDatabaseHas('erp_warehouses', ['warehouse_code' => 'WH-B', 'is_active' => 0], 'tenant');
        $this->getJson(route('erp.warehouses.next_code'))->assertJson(['next_code' => 'WH002']);

        $this->postJson(route('erp.warehouses.store'), ['warehouse_code' => 'WH001', 'name' => 'Dup'])
            ->assertStatus(422)->assertJsonValidationErrors('warehouse_code');
        $this->postJson(route('erp.warehouses.store'), ['capacity' => 'lots', 'last_stock_take_date' => 'not-a-date'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'capacity', 'last_stock_take_date']);
        $this->assertSame(2, ErpWarehouse::count());
    }

    public function test_update_and_delete(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $wh = $this->warehouse(['warehouse_code' => 'WH001']);
        $this->warehouse(['warehouse_code' => 'WH002']);

        $this->putJson(route('erp.warehouses.update', $wh), ['warehouse_code' => 'WH002', 'name' => 'X'])
            ->assertStatus(422)->assertJsonValidationErrors('warehouse_code');

        $this->putJson(route('erp.warehouses.update', $wh), ['name' => 'Gudang Baru', 'address' => 'Jl. Raya 1'])->assertOk();
        $this->assertDatabaseHas('erp_warehouses', ['id' => $wh->id, 'warehouse_code' => 'WH001', 'name' => 'Gudang Baru', 'address' => 'Jl. Raya 1', 'is_active' => 0], 'tenant');

        $this->put(route('erp.warehouses.update', $wh), ['name' => 'From form', 'is_active' => '1'])
            ->assertRedirect(route('erp.warehouses.show', $wh));
        $this->assertDatabaseHas('erp_warehouses', ['id' => $wh->id, 'is_active' => 1], 'tenant');

        $this->deleteJson(route('erp.warehouses.destroy', $wh))->assertOk();
        $this->assertDatabaseMissing('erp_warehouses', ['id' => $wh->id], 'tenant');
    }

    public function test_user_without_warehouse_permission_is_refused(): void
    {
        $wh = $this->warehouse(['name' => 'Keep']);

        $this->actingInProject($this->makeUser('viewer', ['warehouses.view']));
        $this->get(route('erp.warehouses.index'))->assertOk();
        $row = $this->datatable('erp.warehouses.datatable', '', 0, 'asc', 'get')->json('data.0.actions');
        $this->assertStringNotContainsString('js-edit', $row);
        $this->assertStringNotContainsString('js-del', $row);

        $this->actingInProject($this->makeUser('staff'));
        $this->get(route('erp.warehouses.index'))->assertForbidden();
        $this->postJson(route('erp.warehouses.store'), ['name' => 'Nope'])->assertForbidden();
        $this->putJson(route('erp.warehouses.update', $wh), ['name' => 'Nope'])->assertForbidden();
        $this->deleteJson(route('erp.warehouses.destroy', $wh))->assertForbidden();

        $this->assertDatabaseHas('erp_warehouses', ['id' => $wh->id, 'name' => 'Keep'], 'tenant');
        $this->assertSame(1, ErpWarehouse::count());
    }

    public function test_permission_keys_granted_by_the_roles_screen_work(): void
    {

        $this->actingInProject($this->makeUser('logistik', ['warehouses.view', 'warehouses.create']));
        $this->get(route('erp.warehouses.index'))->assertOk();
        $this->postJson(route('erp.warehouses.store'), ['name' => 'Allowed'])->assertOk();
    }

    public function test_datatable_requires_warehouse_permission(): void
    {

        $this->warehouse();
        $this->actingInProject($this->makeUser('staff'));
        $this->datatable('erp.warehouses.datatable', '', 0, 'asc', 'get')->assertForbidden();
    }
}
