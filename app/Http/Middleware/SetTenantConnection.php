<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Project;
use App\Services\ErpTenantManager;

class SetTenantConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isGuestRoute($request)) {
            return $next($request);
        }

        if (auth()->check()) {
            $projectId = $request->session()->get('project_id');

            if (!$projectId) {
                // Jangan redirect jika sedang berada di halaman pemilihan project
                if ($request->routeIs('projects.switch') || $request->routeIs('projects.switch.process')) {
                    return $next($request);
                }
                return redirect()->route('projects.switch');
            }

            $project = Project::find($projectId);
            
            if (!$project || !$project->is_active) {
                $request->session()->forget('project_id');
                return redirect()->route('projects.switch')->with('error', 'Project tidak valid atau tidak aktif.');
            }

            // Validasi akses user ke project ini
            if (!auth()->user()->hasRole('superadmin') && !auth()->user()->projects()->where('project_id', $projectId)->exists()) {
                $request->session()->forget('project_id');
                return redirect()->route('projects.switch')->with('error', 'Anda tidak memiliki akses ke project ini.');
            }

            ErpTenantManager::switchToProject($project);
        }

        return $next($request);
    }

    private function isGuestRoute(Request $request): bool
    {
        $guestRoutes = [
            'login',
            'register',
            'password.request',
            'password.reset',
            'password.email',
            'logout',
        ];

        $currentRoute = $request->route()?->getName();

        return $currentRoute && in_array($currentRoute, $guestRoutes);
    }
}
