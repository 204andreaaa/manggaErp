<?php

namespace Tests\Feature\Erp;

use Illuminate\Http\UploadedFile;
use Tests\Feature\Erp\PurchaseOrder\PurchaseOrderTestCase;

/** Each project (tenant) is one PT: the superadmin sets its identity, and it is the letterhead of the PO. */
class TenantIdentityTest extends PurchaseOrderTestCase
{
    public function test_superadmin_sets_the_pt_identity_and_the_po_prints_it(): void
    {
        $admin = $this->makeUser('superadmin');

        $this->actingInProject($admin)->put(route('erp.projects.update', $this->project), [
            'name' => 'Imprima', 'is_active' => 1,
            'legal_name' => 'PT Intertel Media Prima', 'address' => "Jl. Contoh No. 1\nJakarta 12345",
            'phone' => '021-1234567', 'tax_number' => '01.234.567.8-901.000',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertSessionHasNoErrors();

        $project = $this->project->fresh();
        $this->assertSame('PT Intertel Media Prima', $project->letterheadName());
        $this->assertNotNull($project->logoUrl());
        @unlink(public_path($project->logo_path));

        $po = $this->makeApprovedPo($this->makeApprovedRf([['name' => 'Kabel', 'qty' => 1, 'unit_cost' => 100_000]]), ['invoice_to' => null, 'attention_to' => null]);
        $this->get(route('erp.purchase-orders.print', $po))->assertOk()
            ->assertSee('PT Intertel Media Prima')
            ->assertSee('Finance PT Intertel Media Prima')
            ->assertSee('Jakarta 12345')
            ->assertSee('01.234.567.8-901.000')
            ->assertDontSee('Mandiri Daya Utama');
    }

    public function test_the_tenant_pages_call_it_perusahaan_and_show_the_legal_name(): void
    {
        $this->project->update(['legal_name' => 'PT Mandiri Daya Utama Nusantara']);
        $admin = $this->makeUser('superadmin');

        $this->actingInProject($admin)->get(route('projects.switch', ['select' => 1]))->assertOk()
            ->assertSee('PERUSAHAAN')->assertSee('Identitas PT')->assertSee('PT Mandiri Daya Utama Nusantara');
        $this->get(route('erp.projects.index'))->assertOk()
            ->assertSee('Perusahaan (Tenant)')->assertSee('PT Mandiri Daya Utama Nusantara');
    }

    public function test_only_the_superadmin_changes_the_pt_identity(): void
    {
        $this->actingInProject($this->makeUser('admin'))->put(route('erp.projects.update', $this->project), [
            'name' => 'X', 'is_active' => 1, 'legal_name' => 'PT Palsu',
        ])->assertForbidden();

        $this->assertNull($this->project->fresh()->legal_name);
    }
}
