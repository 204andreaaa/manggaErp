<?php

use App\Services\TenantManager;
use Illuminate\Support\Facades\DB;

/**
 * Get current project ID
 */
function currentProject()
{
    return TenantManager::getCurrentProject();
}

/**
 * Get current project name
 */
function currentProjectName()
{
    return TenantManager::getCurrentProjectName();
}

/**
 * Get user projects
 */
function userProjects($userId = null)
{
    $userId = $userId ?: auth()->id();
    return TenantManager::getUserProjects($userId);
}

/**
 * Get all projects
 */
function allProjects()
{
    return TenantManager::getAllProjects();
}

/**
 * Switch project
 */
function switchProject($projectId)
{
    return TenantManager::switchToProject($projectId);
}

/**
 * Get tenant connection
 */
function tenantDB()
{
    return DB::connection('tenant');
}
