<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Erp\ErpSupplier;
use App\Models\Erp\ErpSupplierAttachment;
use App\Models\Erp\ErpSupplierContact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ErpTestCase;

class SupplierTest extends ErpTestCase
{
    use MasterDataHelpers;

    private function supplier(array $attributes = []): ErpSupplier
    {
        return ErpSupplier::create(array_merge([
            'supplier_code' => 'SUP-'.random_int(100, 999),
            'name' => 'PT Semen Jaya',
        ], $attributes));
    }

    public function test_list_and_detail_pages_open_and_datatable_searches_and_orders(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $a = $this->supplier(['supplier_code' => 'SUP-001', 'name' => 'PT Semen Jaya', 'bank_name' => 'BCA']);
        $this->supplier(['supplier_code' => 'SUP-002', 'name' => 'CV Baja Abadi', 'bank_name' => 'Mandiri']);

        $this->get(route('erp.suppliers.index'))->assertOk();
        $this->get(route('erp.suppliers.show', $a))->assertOk()->assertSee('PT Semen Jaya');

        $res = $this->datatable('erp.suppliers.datatable', '', 2, 'asc', 'get')->assertOk();
        $res->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 2]);
        $this->assertStringContainsString('CV Baja Abadi', $res->json('data.0.name'));

        $search = $this->datatable('erp.suppliers.datatable', 'mandiri', 0, 'asc', 'get');
        $search->assertJson(['recordsTotal' => 2, 'recordsFiltered' => 1]);
        $this->assertSame('SUP-002', $search->json('data.0.supplier_code'));
    }

    public function test_create_generates_code_and_rejects_duplicates_and_missing_name(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        $this->postJson(route('erp.suppliers.store'), ['name' => 'PT Semen Jaya', 'phone' => '0211234', 'bank_account' => '123'])
            ->assertOk()->assertJsonStructure(['success']);
        $this->assertDatabaseHas('erp_suppliers', ['supplier_code' => 'SUP-001', 'name' => 'PT Semen Jaya', 'phone' => '0211234'], 'tenant');
        $this->getJson(route('erp.suppliers.next_code'))->assertJson(['next_code' => 'SUP-002']);

        $this->postJson(route('erp.suppliers.store'), ['supplier_code' => 'sup-001', 'name' => 'Dup'])
            ->assertStatus(422)->assertJsonValidationErrors('supplier_code');
        $this->postJson(route('erp.suppliers.store'), ['supplier_code' => 'SUP-009', 'payment_terms_id' => 999999])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'payment_terms_id']);
        $this->assertSame(1, ErpSupplier::count());
    }

    public function test_update_and_delete(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $supplier = $this->supplier(['supplier_code' => 'SUP-001']);
        $this->supplier(['supplier_code' => 'SUP-002']);

        $this->putJson(route('erp.suppliers.update', $supplier), ['supplier_code' => 'SUP-002', 'name' => 'X'])
            ->assertStatus(422)->assertJsonValidationErrors('supplier_code');

        $this->putJson(route('erp.suppliers.update', $supplier), ['supplier_code' => '', 'name' => 'PT Renamed', 'website' => 'example.com'])
            ->assertOk();
        $this->assertDatabaseHas('erp_suppliers', ['id' => $supplier->id, 'supplier_code' => 'SUP-001', 'name' => 'PT Renamed', 'website' => 'example.com'], 'tenant');

        // The detail page posts a normal form and is redirected back to itself.
        $this->put(route('erp.suppliers.update', $supplier), ['name' => 'PT From Form'])
            ->assertRedirect(route('erp.suppliers.show', $supplier));

        $this->deleteJson(route('erp.suppliers.destroy', $supplier))->assertOk();
        $this->assertDatabaseMissing('erp_suppliers', ['id' => $supplier->id], 'tenant');
    }

    public function test_contacts_and_attachments_are_added_and_removed(): void
    {
        Storage::fake('public');
        $this->actingInProject($this->makeUser('superadmin'));
        $supplier = $this->supplier();

        $this->post(route('erp.suppliers.contacts.store', $supplier), ['contact_name' => 'Budi', 'email' => 'budi@x.test', 'phone' => '0812'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $contact = ErpSupplierContact::where('erp_supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('Budi', $contact->contact_name);

        $this->post(route('erp.suppliers.contacts.store', $supplier), ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['contact_name', 'email']);

        $this->post(route('erp.suppliers.attachments.store', $supplier), [
            'title' => 'NPWP',
            'file' => UploadedFile::fake()->create('npwp.pdf', 20, 'application/pdf'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        $attachment = ErpSupplierAttachment::where('erp_supplier_id', $supplier->id)->firstOrFail();
        Storage::disk('public')->assertExists($attachment->file_path);

        $this->post(route('erp.suppliers.attachments.store', $supplier), [
            'title' => 'Script',
            'file' => UploadedFile::fake()->create('x.exe', 5, 'application/octet-stream'),
        ])->assertSessionHasErrors('file');

        $this->delete(route('erp.suppliers.attachments.destroy', $attachment))->assertRedirect();
        Storage::disk('public')->assertMissing($attachment->file_path);
        $this->assertDatabaseMissing('erp_supplier_attachments', ['id' => $attachment->id], 'tenant');

        $this->delete(route('erp.suppliers.contacts.destroy', $contact))->assertRedirect();
        $this->assertDatabaseMissing('erp_supplier_contacts', ['id' => $contact->id], 'tenant');
    }

    public function test_user_without_supplier_permission_is_refused(): void
    {
        $supplier = $this->supplier(['supplier_code' => 'SUP-001', 'name' => 'Keep']);
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.suppliers.index'))->assertForbidden();
        $this->get(route('erp.suppliers.show', $supplier))->assertForbidden();
        $this->postJson(route('erp.suppliers.store'), ['name' => 'Nope'])->assertForbidden();
        $this->putJson(route('erp.suppliers.update', $supplier), ['name' => 'Nope'])->assertForbidden();
        $this->deleteJson(route('erp.suppliers.destroy', $supplier))->assertForbidden();

        $this->assertDatabaseHas('erp_suppliers', ['id' => $supplier->id, 'name' => 'Keep'], 'tenant');
        $this->assertSame(1, ErpSupplier::count());
    }

    public function test_procurement_and_finance_roles_reach_the_supplier_screens(): void
    {
        $supplier = $this->supplier();

        $this->actingInProject($this->makeUser('finance'));
        $this->get(route('erp.suppliers.index'))->assertOk();
        $this->postJson(route('erp.suppliers.store'), ['name' => 'Nope'])->assertForbidden();

        $this->actingInProject($this->makeUser('procurement'));
        $this->postJson(route('erp.suppliers.store'), ['name' => 'By procurement'])->assertOk();
        $this->deleteJson(route('erp.suppliers.destroy', $supplier))->assertOk();
        $this->assertDatabaseHas('erp_suppliers', ['name' => 'By procurement'], 'tenant');
    }

    public function test_contact_and_attachment_endpoints_require_supplier_permission(): void
    {

        Storage::fake('public');
        $supplier = $this->supplier();
        $contact = $supplier->contacts()->create(['contact_name' => 'Keep']);
        $attachment = $supplier->attachments()->create(['type' => 'Attachment', 'title' => 'Keep', 'file_path' => 'erp/attachments/keep.pdf']);
        $this->actingInProject($this->makeUser('staff'));

        $this->post(route('erp.suppliers.contacts.store', $supplier), ['contact_name' => 'Intruder'])->assertForbidden();
        $this->delete(route('erp.suppliers.contacts.destroy', $contact))->assertForbidden();
        $this->post(route('erp.suppliers.attachments.store', $supplier), [
            'title' => 'Intruder', 'file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'),
        ])->assertForbidden();
        $this->delete(route('erp.suppliers.attachments.destroy', $attachment))->assertForbidden();

        $this->assertSame(1, $supplier->contacts()->count());
        $this->assertSame(1, $supplier->attachments()->count());
    }

    public function test_datatable_and_next_code_require_supplier_permission(): void
    {

        $this->supplier();
        $this->actingInProject($this->makeUser('staff'));

        $this->datatable('erp.suppliers.datatable', '', 0, 'asc', 'get')->assertForbidden();
        $this->getJson(route('erp.suppliers.next_code'))->assertForbidden();
    }

    public function test_permission_keys_granted_by_the_roles_screen_work(): void
    {

        $this->actingInProject($this->makeUser('vendor_admin', ['suppliers.view', 'suppliers.create']));
        $this->get(route('erp.suppliers.index'))->assertOk();
        $this->postJson(route('erp.suppliers.store'), ['name' => 'Allowed'])->assertOk();
    }
}
