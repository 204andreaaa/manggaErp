<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Erp\Brand;
use App\Models\Erp\Currency;
use App\Models\Erp\ErpProduct;
use App\Models\Erp\Uom;
use Illuminate\Http\UploadedFile;
use Tests\ErpTestCase;

class ProductCatalogTest extends ErpTestCase
{
    use MasterDataHelpers;

    private function product(array $attributes = []): ErpProduct
    {
        return ErpProduct::create(array_merge([
            'product_code' => 'EPRD-'.random_int(100, 999),
            'name' => 'Cement 50kg',
            'buying_price' => 65000,
            'is_physical' => true,
            'is_active' => true,
        ], $attributes));
    }

    public function test_list_page_opens_and_datatable_searches_and_orders(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->product(['product_code' => 'EPRD-001', 'name' => 'Cement 50kg', 'part_number' => 'PN-CEM']);
        $this->product(['product_code' => 'EPRD-002', 'name' => 'Steel Rebar 10mm']);

        $this->get(route('erp.products.index'))->assertOk()->assertSee('EPRD-003');

        $res = $this->datatable('erp.products.datatable', '', 1, 'desc')->assertOk();
        $res->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 2]);
        $this->assertStringContainsString('EPRD-002', $res->json('data.0.product_code'));

        $search = $this->datatable('erp.products.datatable', 'PN-CEM');
        $search->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1]);
        $this->assertStringContainsString('Cement 50kg', $search->json('data.0.product'));

        $this->getJson(route('erp.products.next_code'))->assertOk()->assertJson(['next_code' => 'EPRD-003']);
    }

    public function test_create_with_generated_code_and_lookups(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $uom = Uom::create(['uom_name' => 'Sak']);
        $brand = Brand::create(['brand_name' => 'Tiga Roda']);
        $idr = Currency::create(['code' => 'IDR', 'name' => 'Rupiah', 'symbol' => 'Rp']);

        $this->postJson(route('erp.products.store'), [
            'product_code' => '',
            'name' => 'Cement 50kg',
            'buying_price' => 65000,
            'uom_id' => $uom->id,
            'brand_id' => $brand->id,
            'currency_id' => $idr->id,
            'product_family_id' => 'null',
            'is_physical' => 0,
        ])->assertOk()->assertJsonStructure(['success']);

        $this->assertDatabaseHas('erp_products', [
            'product_code' => 'EPRD-001',
            'name' => 'Cement 50kg',
            'uom_id' => $uom->id,
            'brand_id' => $brand->id,
            'currency_id' => $idr->id,
            'product_family_id' => null,
            'is_physical' => 0,
            'is_active' => 1,
        ], 'tenant');

        // Typed codes are upper-cased.
        $this->postJson(route('erp.products.store'), ['product_code' => 'abc-1', 'name' => 'X', 'buying_price' => 1])->assertOk();
        $this->assertDatabaseHas('erp_products', ['product_code' => 'ABC-1'], 'tenant');
    }

    public function test_export_lists_products_with_uom_brand_and_price(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->product([
            'product_code' => 'EPRD-777',
            'uom_id' => Uom::create(['uom_name' => 'Sak'])->id,
            'brand_id' => Brand::create(['brand_name' => 'Tiga Roda'])->id,
        ]);

        $csv = $this->get(route('erp.products.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('EPRD-777,-,"Cement 50kg",Sak,"Tiga Roda",65000', $csv);
    }

    public function test_create_rejects_duplicate_code_bad_lookup_and_missing_fields(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->product(['product_code' => 'EPRD-001']);

        $this->postJson(route('erp.products.store'), ['product_code' => 'eprd-001', 'name' => 'Dup', 'buying_price' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('product_code');
        $this->postJson(route('erp.products.store'), ['name' => 'Bad uom', 'buying_price' => 1, 'uom_id' => 999999])
            ->assertStatus(422)->assertJsonValidationErrors('uom_id');
        $this->postJson(route('erp.products.store'), ['buying_price' => -5])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'buying_price']);

        $this->assertSame(1, ErpProduct::count());
    }

    public function test_update_and_soft_delete_frees_the_code(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $product = $this->product(['product_code' => 'EPRD-001']);
        $this->product(['product_code' => 'EPRD-002']);

        $this->putJson(route('erp.products.update', $product), ['product_code' => 'EPRD-002', 'name' => 'X', 'buying_price' => 1])
            ->assertStatus(422)->assertJsonValidationErrors('product_code');

        // is_active is a checkbox on edit: leaving it out deactivates.
        $this->putJson(route('erp.products.update', $product), ['product_code' => '', 'name' => 'Cement 40kg', 'buying_price' => 60000])
            ->assertOk();
        $this->assertDatabaseHas('erp_products', ['id' => $product->id, 'product_code' => 'EPRD-001', 'name' => 'Cement 40kg', 'buying_price' => 60000, 'is_active' => 0], 'tenant');

        $this->deleteJson(route('erp.products.destroy', $product))->assertOk();
        $this->assertSoftDeleted('erp_products', ['id' => $product->id], 'tenant');

        $this->postJson(route('erp.products.store'), ['product_code' => 'EPRD-001', 'name' => 'Reused', 'buying_price' => 1])->assertOk();
        // ...but the generated code never reuses a deleted one.
        $this->getJson(route('erp.products.next_code'))->assertJson(['next_code' => 'EPRD-003']);
    }

    public function test_image_upload_is_saved_and_replaced(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $written = [];

        try {
            $this->post(route('erp.products.store'), [
                'name' => 'With image', 'buying_price' => 1,
                'image' => UploadedFile::fake()->image('p.png', 20, 20),
            ], ['Accept' => 'application/json'])->assertOk();

            $product = ErpProduct::where('name', 'With image')->firstOrFail();
            $written[] = $first = public_path($product->image);
            $this->assertStringStartsWith('uploads/products/', $product->image);
            $this->assertFileExists($first);

            $this->post(route('erp.products.update', $product), [
                '_method' => 'PUT', 'name' => 'With image', 'buying_price' => 1, 'is_active' => 1,
                'image' => UploadedFile::fake()->image('q.jpg', 20, 20),
            ], ['Accept' => 'application/json'])->assertOk();

            $written[] = $second = public_path($product->fresh()->image);
            $this->assertNotSame($first, $second);
            $this->assertFileExists($second);
            $this->assertFileDoesNotExist($first);

            $this->postJson(route('erp.products.store'), [
                'name' => 'Not an image', 'buying_price' => 1,
                'image' => UploadedFile::fake()->create('evil.php', 1, 'application/x-php'),
            ])->assertStatus(422)->assertJsonValidationErrors('image');
        } finally {
            foreach ($written as $file) {
                @unlink($file);
            }
        }
    }

    public function test_user_without_products_permission_is_refused(): void
    {
        $product = $this->product(['product_code' => 'EPRD-001', 'name' => 'Keep']);
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.products.index'))->assertForbidden();
        $this->datatable('erp.products.datatable')->assertForbidden();
        $this->postJson(route('erp.products.store'), ['name' => 'Nope', 'buying_price' => 1])->assertForbidden();
        $this->putJson(route('erp.products.update', $product), ['name' => 'Nope', 'buying_price' => 1])->assertForbidden();
        $this->deleteJson(route('erp.products.destroy', $product))->assertForbidden();

        $this->assertDatabaseHas('erp_products', ['id' => $product->id, 'name' => 'Keep', 'deleted_at' => null], 'tenant');
        $this->assertSame(1, ErpProduct::count());
    }

    public function test_datatable_edit_button_does_not_allow_stored_xss(): void
    {

        $this->actingInProject($this->makeUser('superadmin'));
        $payload = "');alert(1)//";
        $this->product(['product_code' => 'EPRD-001', 'name' => $payload]);

        $html = $this->datatable('erp.products.datatable')->assertOk()->json('data.0.actions');
        $this->assertOnclickDoesNotExecute($html, $payload);
    }
}
