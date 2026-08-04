<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectLogoTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_superadmin_can_create_edit_and_delete_project_with_logo(): void
    {
        // 1. Create a superadmin user
        $superadmin = User::factory()->create([
            'role' => 'superadmin',
            'username' => 'superadmin_test_' . rand(1000, 9999),
        ]);

        $this->actingAs($superadmin);

        // 2. Prepare logo file
        $logo = UploadedFile::fake()->image('project_logo.png', 100, 100);

        // Unique name for testing
        $projectName = 'Test Brand New Project ' . time();
        $dbName = 'mangga_test_logo_' . time() . '_db';

        // 3. Post to create project
        $response = $this->post(route('projects.manage.store'), [
            'name' => $projectName,
            'db_name' => $dbName,
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('projects.manage'));

        // Retrieve project from database
        if (session('errors')) {
            fwrite(STDERR, print_r(session('errors')->getMessages(), true));
        }
        $project = DB::connection('master')->table('projects')
            ->where('db_name', $dbName)
            ->first();

        $this->assertNotNull($project, 'Project was not created');
        $this->assertNotNull($project->logo_path, 'Logo path should not be null');
        
        $logoFullPath = public_path($project->logo_path);
        $this->assertFileExists($logoFullPath, 'Logo file does not exist on disk');

        // 4. Test updating the project (both name and new logo)
        $newLogo = UploadedFile::fake()->image('updated_logo.png', 120, 120);
        $updatedName = $projectName . ' Updated';

        // PUT request to update
        $updateResponse = $this->put(route('projects.update', ['project_id' => $project->id]), [
            'name' => $updatedName,
            'logo' => $newLogo,
        ]);

        $updateResponse->assertJson(['success' => true]);

        // Verify updated record
        $updatedProject = DB::connection('master')->table('projects')
            ->where('id', $project->id)
            ->first();

        $this->assertEquals($updatedName, $updatedProject->name);
        $this->assertNotEquals($project->logo_path, $updatedProject->logo_path);
        
        // The old logo file should be deleted from disk
        $this->assertFileDoesNotExist($logoFullPath, 'Old logo file was not deleted after update');

        // The new logo file should exist on disk
        $newLogoFullPath = public_path($updatedProject->logo_path);
        $this->assertFileExists($newLogoFullPath, 'New logo file does not exist on disk');

        // 5. Test deleting the project (should also delete the new logo file)
        session()->forget('current_project');
        $deleteResponse = $this->delete(route('projects.destroy', ['project_id' => $project->id]));
        
        $deleteResponse->assertJson(['success' => true]);

        // Verify database records are deleted
        $deletedProject = DB::connection('master')->table('projects')
            ->where('id', $project->id)
            ->first();
        $this->assertNull($deletedProject, 'Project record was not deleted from database');

        // The logo file should be deleted from disk
        $this->assertFileDoesNotExist($newLogoFullPath, 'Logo file was not deleted after project destruction');
    }
}
