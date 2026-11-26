<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Our own companies (MANDAU, IMPRIMA, ...), picked on invoices and vehicles. They live in the
 * master database and are shared by every project, so only admins manage them. A company is
 * never deleted (records in the projects point to it); it is set inactive instead.
 */
class CompanyController extends Controller
{
    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()->hasRole(['superadmin', 'admin']), 403);
    }

    public function index()
    {
        $this->authorizeAdmin();

        return view('erp.companies.index', [
            'companies' => Company::orderByDesc('is_active')->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorizeAdmin();

        return view('erp.companies.form', ['company' => new Company(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $company = $this->save(new Company(), $request);

        return redirect()->route('erp.companies.index')->with('success', "Perusahaan {$company->name} ditambahkan.");
    }

    public function edit(Company $company)
    {
        $this->authorizeAdmin();

        return view('erp.companies.form', ['company' => $company]);
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeAdmin();
        $this->save($company, $request);

        return redirect()->route('erp.companies.index')->with('success', "Perusahaan {$company->name} diperbarui.");
    }

    private function save(Company $company, Request $request): Company
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('master.companies', 'code')->ignore($company->id)],
            'name' => ['required', 'string', 'max:150'],
            'short_name' => ['nullable', 'string', 'max:50'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:200'],
            'tax_number' => ['nullable', 'string', 'max:50'],
        ], [
            'code.unique' => 'Kode perusahaan ini sudah dipakai.',
            'code.alpha_dash' => 'Kode hanya boleh huruf, angka, - dan _ (tanpa spasi).',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');
        // the default company is always one that can be picked
        $data['is_default'] = $request->boolean('is_default') && $data['is_active'];

        DB::connection('master')->transaction(function () use ($company, $data) {
            if ($data['is_default']) {
                Company::where('is_default', true)->whereKeyNot($company->id ?? 0)->update(['is_default' => false]);
            }
            $company->fill($data)->save();
        });

        return $company;
    }
}
