<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ErpProjectProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected function ensureSuperadmin()
    {
        if (!auth()->user()->hasRole('superadmin')) {
            abort(403, 'Hanya superadmin yang dapat mengelola perusahaan (tenant).');
        }
    }

    public function index()
    {
        $this->ensureSuperadmin();
        $projects = Project::orderBy('id', 'desc')->get();
        return view('erp.projects.index', compact('projects'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperadmin();
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'db_name' => 'required|string|unique:master.projects,db_name|regex:/^[a-zA-Z0-9_]+$/',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::create([
                'name' => $data['name'],
                'db_name' => $data['db_name'],
                'db_host' => env('DB_HOST', '127.0.0.1'),
                'db_port' => env('DB_PORT', '3306'),
                'db_username' => env('DB_USERNAME', 'root'),
                'db_password' => env('DB_PASSWORD', ''),
                'is_active' => true,
            ]);

            // Provision Database & Run Migrations
            ErpProjectProvisioner::provision($project);

            DB::commit();
            return back()->with('success', 'Perusahaan dan database berhasil dibuat! Lengkapi identitas PT-nya lewat tombol Edit.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat perusahaan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Project $project)
    {
        $this->ensureSuperadmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'legal_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:100',
            'fax' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = 'logo_' . $project->id . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/logos'), $filename);
            $data['logo_path'] = 'uploads/logos/' . $filename;
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
        }
        unset($data['logo'], $data['remove_logo']);

        $project->update($data);
        return back()->with('success', 'Perusahaan berhasil diupdate!');
    }

    public function destroy(Project $project)
    {
        $this->ensureSuperadmin();

        try {
            $project->delete();
            return back()->with('success', 'Perusahaan berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus perusahaan: ' . $e->getMessage());
        }
    }
}
