<?php

namespace Tests\Feature\Erp\MasterData;

use Illuminate\Support\Facades\DB;
use Tests\ErpTestCase;

/**
 * Brand, Product Family, Product Type, Product Model, UOM and Currency screens.
 * They share one controller shape (guarded by the products.* permissions).
 */
class ProductAttributeTest extends ErpTestCase
{
    use MasterDataHelpers;

    /** [route prefix, table, name field, extra valid fields] */
    public static function screens(): array
    {
        return [
            'brands' => ['brands', 'brands', 'brand_name', ['description' => 'desc']],
            'product families' => ['product-families', 'product_families', 'family_name', ['description' => 'desc']],
            'product types' => ['product-types', 'product_types', 'type_name', ['description' => 'desc']],
            'product models' => ['product-models', 'product_models', 'model_name', ['description' => 'desc']],
            'uoms' => ['uoms', 'uoms', 'uom_name', ['description' => 'desc']],
            'currencies' => ['currencies', 'currencies', 'code', ['name' => 'Some Currency', 'symbol' => '$']],
        ];
    }

    private function insertRow(string $table, string $field, string $value, array $extra): int
    {
        return DB::connection('tenant')->table($table)->insertGetId(array_merge($extra, [
            $field => $value,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /** @dataProvider screens */
    public function test_list_page_opens_and_datatable_lists_searches_and_orders(string $prefix, string $table, string $field, array $extra): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->insertRow($table, $field, 'ZZ1', $extra);
        $this->insertRow($table, $field, 'AA1', $extra);

        $this->get(route("erp.{$prefix}.index"))->assertOk();

        $all = $this->datatable("erp.{$prefix}.datatable", '', 1, 'asc')->assertOk();
        $all->assertJson(['draw' => 3, 'recordsTotal' => 2, 'recordsFiltered' => 2]);
        $this->assertSame(['AA1', 'ZZ1'], array_column($all->json('data'), $field));

        $desc = $this->datatable("erp.{$prefix}.datatable", '', 1, 'desc');
        $this->assertSame(['ZZ1', 'AA1'], array_column($desc->json('data'), $field));

        $found = $this->datatable("erp.{$prefix}.datatable", 'ZZ');
        $found->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1]);
        $this->assertSame('ZZ1', $found->json('data.0.'.$field));
        $this->assertStringContainsString('openEdit(', $found->json('data.0.actions'));
        $this->assertStringContainsString('deleteItem(', $found->json('data.0.actions'));
    }

    /** @dataProvider screens */
    public function test_create_persists_and_duplicate_or_missing_name_is_rejected(string $prefix, string $table, string $field, array $extra): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        $this->postJson(route("erp.{$prefix}.store"), array_merge($extra, [$field => 'NEW1']))
            ->assertOk()->assertJsonStructure(['success']);
        $this->assertDatabaseHas($table, array_merge($extra, [$field => 'NEW1']), 'tenant');

        $this->postJson(route("erp.{$prefix}.store"), array_merge($extra, [$field => 'NEW1']))
            ->assertStatus(422)->assertJsonValidationErrors($field);
        $this->postJson(route("erp.{$prefix}.store"), $extra)
            ->assertStatus(422)->assertJsonValidationErrors($field);
        $this->assertSame(1, DB::connection('tenant')->table($table)->count());
    }

    /** @dataProvider screens */
    public function test_update_and_delete(string $prefix, string $table, string $field, array $extra): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $id = $this->insertRow($table, $field, 'OLD1', $extra);
        $this->insertRow($table, $field, 'TAKEN', $extra);

        // Keeping its own name is fine; taking another row's name is not.
        $this->putJson(route("erp.{$prefix}.update", $id), array_merge($extra, [$field => 'OLD1']))->assertOk();
        $this->putJson(route("erp.{$prefix}.update", $id), array_merge($extra, [$field => 'TAKEN']))
            ->assertStatus(422)->assertJsonValidationErrors($field);

        $this->putJson(route("erp.{$prefix}.update", $id), array_merge($extra, [$field => 'NEW2']))->assertOk();
        $this->assertDatabaseHas($table, ['id' => $id, $field => 'NEW2'], 'tenant');

        $this->deleteJson(route("erp.{$prefix}.destroy", $id))->assertOk();
        $this->assertDatabaseMissing($table, ['id' => $id], 'tenant');
    }

    /** @dataProvider screens */
    public function test_user_without_products_permission_is_refused(string $prefix, string $table, string $field, array $extra): void
    {
        $id = $this->insertRow($table, $field, 'KEEP', $extra);

        // View-only: may list, may not change.
        $this->actingInProject($this->makeUser('viewer', ['products.view']));
        $this->get(route("erp.{$prefix}.index"))->assertOk();
        $row = $this->datatable("erp.{$prefix}.datatable")->assertOk()->json('data.0.actions');
        $this->assertStringNotContainsString('openEdit(', $row);
        $this->assertStringNotContainsString('deleteItem(', $row);

        $this->postJson(route("erp.{$prefix}.store"), array_merge($extra, [$field => 'NOPE']))->assertForbidden();
        $this->putJson(route("erp.{$prefix}.update", $id), array_merge($extra, [$field => 'NOPE']))->assertForbidden();
        $this->deleteJson(route("erp.{$prefix}.destroy", $id))->assertForbidden();

        // No permission at all: not even the list.
        $this->actingInProject($this->makeUser('staff'));
        $this->get(route("erp.{$prefix}.index"))->assertForbidden();
        $this->datatable("erp.{$prefix}.datatable")->assertForbidden();

        $this->assertDatabaseHas($table, ['id' => $id, $field => 'KEEP'], 'tenant');
        $this->assertSame(1, DB::connection('tenant')->table($table)->count());
    }

    /** @dataProvider screens */
    public function test_datatable_edit_button_does_not_allow_stored_xss(string $prefix, string $table, string $field, array $extra): void
    {
        $this->skipKnownBug("Erp\\*Controller::datatable builds onclick=\"openEdit(...,'\".addslashes(e(\$name)).\"')\": e() turns ' into &#039; before addslashes, the browser decodes it back and the name breaks out of the JS string (stored XSS).");

        $this->actingInProject($this->makeUser('superadmin'));
        $payload = "');alert(1)//";
        $this->insertRow($table, $field, $payload, $extra);

        $html = $this->datatable("erp.{$prefix}.datatable")->assertOk()->json('data.0.actions');
        $this->assertOnclickDoesNotExecute($html, $payload);
    }
}
