<?php

namespace Tests\Feature\Erp;

use App\Models\Company;
use Tests\ErpTestCase;

/** Our own companies (master DB): admins add and change them; an inactive one is no longer offered. */
class CompanyTest extends ErpTestCase
{
    public function test_admins_add_and_change_companies_and_only_one_is_the_default(): void
    {
        $mandau = Company::firstOrCreate(['code' => 'MANDAU'], ['name' => 'Mandau', 'short_name' => 'MANDAU', 'is_active' => true, 'is_default' => true]);
        $admin = $this->makeUser('superadmin');

        $this->actingInProject($admin)->get(route('erp.companies.index'))->assertOk()->assertSee('MANDAU');

        $this->post(route('erp.companies.store'), ['code' => 'imprima', 'name' => 'Imprima', 'legal_name' => 'PT Imprima Prima Sejahtera',
            'is_active' => 1, 'is_default' => 1])->assertSessionHasNoErrors()->assertRedirect(route('erp.companies.index'));
        $imprima = Company::where('code', 'IMPRIMA')->firstOrFail();
        $this->assertTrue((bool) $imprima->is_default);
        $this->assertFalse((bool) $mandau->fresh()->is_default);

        // codes are unique and have no spaces
        $this->post(route('erp.companies.store'), ['code' => 'IMPRIMA', 'name' => 'X', 'is_active' => 1])->assertSessionHasErrors('code');
        $this->post(route('erp.companies.store'), ['code' => 'MAS X', 'name' => 'X', 'is_active' => 1])->assertSessionHasErrors('code');

        // set inactive: no longer the default and no longer offered on a vehicle
        $this->put(route('erp.companies.update', $imprima), ['code' => 'IMPRIMA', 'name' => 'Imprima', 'is_default' => 1])->assertSessionHasNoErrors();
        $imprima->refresh();
        $this->assertFalse((bool) $imprima->is_active);
        $this->assertFalse((bool) $imprima->is_default);
        $this->get(route('erp.vehicles.create', ['ownership' => 'Rent']))->assertOk()->assertSee('MANDAU')->assertDontSee('>Imprima<', false);
    }

    public function test_only_admins_manage_companies(): void
    {
        $company = Company::firstOrCreate(['code' => 'MANDAU'], ['name' => 'Mandau', 'is_active' => true]);
        $finance = $this->makeUser('head_finance');

        $this->actingInProject($finance)->get(route('erp.companies.index'))->assertForbidden();
        $this->post(route('erp.companies.store'), ['code' => 'X', 'name' => 'X'])->assertForbidden();
        $this->put(route('erp.companies.update', $company), ['code' => 'MANDAU', 'name' => 'Hacked'])->assertForbidden();
        $this->assertSame('Mandau', $company->fresh()->name);
    }
}
