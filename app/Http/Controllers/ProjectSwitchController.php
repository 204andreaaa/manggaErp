<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;

class ProjectSwitchController extends Controller
{
    public function showSwitchForm(Request $request)
    {
        $user = auth()->user();
        
        if ($user->hasRole('superadmin')) {
            $projects = Project::orderBy('id', 'asc')->get();
        } else {
            $projects = $user->projects()->where('is_active', true)->orderBy('id', 'asc')->get();
        }

        if ($projects->isEmpty()) {
            return response("Anda belum memiliki akses ke project ERP apapun. Silakan hubungi Superadmin.", 403);
        }

        // Kalau cuma ada 1 project dan belum pernah pilih project, langsung switch
        if ($projects->count() === 1 && !$request->has('select') && !$request->session()->has('project_id')) {
            return $this->processSwitch($request, $projects->first()->id);
        }

        return view('erp.projects.switch', compact('projects', 'user'));
    }

    public function processSwitch(Request $request, $id = null)
    {
        $projectId = $id ?? $request->input('project_id');
        
        $project = Project::findOrFail($projectId);

        $user = auth()->user();
        
        if (!$user->hasRole('superadmin') && !$user->projects()->where('project_id', $projectId)->exists()) {
            return back()->with('error', 'Anda tidak memiliki akses ke project ini.');
        }

        if (!$project->is_active) {
            return back()->with('error', 'Project ini sedang tidak aktif.');
        }

        $request->session()->put('project_id', $project->id);

        return redirect()->route('dashboard');
    }
}
