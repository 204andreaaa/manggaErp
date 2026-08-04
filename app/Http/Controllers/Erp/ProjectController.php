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
            abort(403, 'Hanya superadmin yang dapat mengelola project/tenant.');
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
            'db_name' => 'required|string|unique:projects,db_name|regex:/^[a-zA-Z0-9_]+$/',
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
            return back()->with('success', 'Project dan Database berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat project: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Project $project)
    {
        $this->ensureSuperadmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $project->update($data);
        return back()->with('success', 'Project berhasil diupdate!');
    }

    public function destroy(Project $project)
    {
        $this->ensureSuperadmin();

        try {
            $project->delete();
            return back()->with('success', 'Project berhasil dihapus!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus project: ' . $e->getMessage());
        }
    }
}
