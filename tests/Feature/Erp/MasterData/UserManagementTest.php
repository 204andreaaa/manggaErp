<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\ErpTestCase;

class UserManagementTest extends ErpTestCase
{
    use MasterDataHelpers;

    /** The master department list the role guess points into. */
    private function seedDepartments(): void
    {
        foreach (['PROC' => 'Procurement & Purchasing', 'GA' => 'General Affair (GA)', 'FIN' => 'Finance & Accounting'] as $code => $name) {
            Department::firstOrCreate(['code' => $code], ['name' => $name, 'status' => 'active']);
        }
    }

    private function validUser(array $overrides = []): array
    {
        Role::firstOrCreate(['slug' => 'procurement'], ['name' => 'Procurement']);
        $this->seedDepartments();

        return array_merge([
            'name' => 'Rina Procurement',
            'username' => 'rina',
            'email' => 'rina@test.local',
            'phone' => '0811111',
            'position' => 'Buyer',
            'department' => 'Procurement & Purchasing',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'roles' => ['procurement'],
            'status' => 'active',
        ], $overrides);
    }

    public function test_list_page_shows_users(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->makeUser('staff', [], ['name' => 'Joko Lapangan']);

        $this->get(route('erp.users.index'))->assertOk()->assertSee('Joko Lapangan');
    }

    public function test_create_user_with_role_project_employee_profile_and_signature(): void
    {
        Storage::fake('public');
        $this->actingInProject($this->makeUser('superadmin'));

        $this->from(route('erp.users.index'))
            ->post(route('erp.users.store'), $this->validUser([
                'signature' => UploadedFile::fake()->image('sig.png', 40, 20),
            ]))
            ->assertRedirect(route('erp.users.index'))
            ->assertSessionHasNoErrors();

        $user = User::where('username', 'rina')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertSame(['procurement'], $user->roles->pluck('slug')->all());
        $this->assertTrue($user->projects()->whereKey($this->project->id)->exists(), 'new user is attached to the current project');
        Storage::disk('public')->assertExists($user->signature_path);

        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Procurement & Purchasing', $employee->department);
        $this->assertSame(sprintf('EMP-%04d', $user->id), $employee->nik);
    }

    public function test_create_rejects_duplicates_bad_password_and_unknown_role(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->makeUser('staff', [], ['username' => 'rina', 'email' => 'rina@test.local']);

        $this->from(route('erp.users.index'))->post(route('erp.users.store'), $this->validUser())
            ->assertSessionHasErrors(['username', 'email']);

        $this->post(route('erp.users.store'), $this->validUser([
            'username' => 'other', 'email' => 'other@test.local',
            'password_confirmation' => 'different', 'roles' => ['no-such-role'], 'status' => 'sleeping',
        ]))->assertSessionHasErrors(['password', 'roles.0', 'status']);

        $this->assertSame(0, User::where('username', 'other')->count());
    }

    public function test_update_changes_name_roles_and_password(): void
    {
        $admin = $this->makeUser('superadmin');
        $target = $this->makeUser('staff', [], ['username' => 'joko', 'email' => 'joko@test.local']);
        $oldHash = $target->password;
        Role::firstOrCreate(['slug' => 'finance'], ['name' => 'Finance']);
        $this->seedDepartments();
        $form = ['name' => 'Joko Finance', 'username' => 'joko', 'email' => 'joko@test.local', 'roles' => ['finance'], 'status' => 'active'];

        $this->actingInProject($admin)
            ->from(route('erp.users.index'))
            ->put(route('erp.users.update', $target), $form + ['password' => ''])
            ->assertRedirect(route('erp.users.index'))
            ->assertSessionHasNoErrors();

        $target->refresh();
        $this->assertSame('Joko Finance', $target->name);
        $this->assertSame($oldHash, $target->password, 'blank password keeps the old one');
        $this->assertSame(['finance'], $target->roles()->pluck('slug')->all());
        $this->assertSame('Finance & Accounting', Employee::where('user_id', $target->id)->value('department'));

        $this->put(route('erp.users.update', $target), $form + ['password' => 'newpass1', 'password_confirmation' => 'newpass1'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('newpass1', $target->fresh()->password));
    }

    public function test_project_access_is_set_from_the_user_form(): void
    {
        $other = \App\Models\Project::create(['name' => 'Imprima', 'db_name' => 'mangga_test_other_'.uniqid(), 'is_active' => true]);
        $admin = $this->makeUser('superadmin');
        $this->actingInProject($admin)
            ->get(route('erp.users.index'))->assertOk()->assertSee('Akses Project')->assertSee('Imprima');

        // create: only the ticked project
        $this->post(route('erp.users.store'), $this->validUser(['projects' => [$other->id]]))->assertSessionHasNoErrors();
        $user = User::where('username', 'rina')->firstOrFail();
        $this->assertSame([$other->id], $user->projects()->pluck('projects.id')->map(fn ($id) => (int) $id)->all());

        // edit: tick both, then untick everything
        $form = ['name' => 'Rina', 'username' => 'rina', 'email' => 'rina@test.local', 'roles' => ['procurement'], 'status' => 'active', 'projects_present' => 1];
        $this->put(route('erp.users.update', $user), $form + ['projects' => [$this->project->id, $other->id]])->assertSessionHasNoErrors();
        $this->assertSame(2, $user->projects()->count());
        $this->put(route('erp.users.update', $user), $form)->assertSessionHasNoErrors();
        $this->assertSame(0, $user->projects()->count());
    }

    public function test_a_non_superadmin_only_hands_out_their_own_projects(): void
    {
        $other = \App\Models\Project::create(['name' => 'Imprima', 'db_name' => 'mangga_test_other_'.uniqid(), 'is_active' => true]);
        $hr = $this->makeUser('hr_admin', ['users.view', 'users.update']);
        $target = $this->makeUser('staff', [], ['username' => 'joko', 'email' => 'joko@test.local']);
        $target->projects()->attach($other->id);
        Role::firstOrCreate(['slug' => 'procurement'], ['name' => 'Procurement']);

        $this->actingInProject($hr)
            ->put(route('erp.users.update', $target), [
                'name' => 'Joko', 'username' => 'joko', 'email' => 'joko@test.local', 'roles' => ['procurement'],
                'status' => 'active', 'projects_present' => 1, 'projects' => [$other->id],
            ])->assertSessionHasNoErrors();

        // the project hr_admin is not in stays as it was; their own project was unticked
        $this->assertSame([$other->id], $target->projects()->pluck('projects.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_department_left_empty_is_guessed_with_procurement_and_ga_apart(): void
    {
        $this->seedDepartments();
        Role::firstOrCreate(['slug' => 'general_affair'], ['name' => 'General Affair']);
        Role::firstOrCreate(['slug' => 'driver'], ['name' => 'Driver']);
        $this->actingInProject($this->makeUser('superadmin'));

        $cases = ['buyer' => ['procurement', 'Procurement & Purchasing'], 'gauser' => ['general_affair', 'General Affair (GA)'], 'driver' => ['driver', null]];
        foreach ($cases as $username => [$role, $expected]) {
            $this->from(route('erp.users.index'))->post(route('erp.users.store'), $this->validUser([
                'username' => $username, 'email' => $username.'@test.local', 'phone' => null, 'department' => '', 'roles' => [$role],
            ]))->assertSessionHasNoErrors();
            $this->assertSame($expected, User::where('username', $username)->first()->employee->department, $username);
        }

        // only names on the master list are accepted
        $this->post(route('erp.users.store'), $this->validUser(['username' => 'x', 'email' => 'x@test.local', 'phone' => null, 'department' => 'Procurement & GA']))
            ->assertSessionHasErrors('department');
    }

    public function test_data_fix_moves_employees_onto_the_master_department_list(): void
    {
        $this->seedDepartments();
        $buyer = $this->makeUser('procurement');
        $ga = $this->makeUser('general_affair');
        $noEmployee = $this->makeUser('finance');
        $unknown = $this->makeUser('driver');
        $kept = $this->makeUser('procurement');
        foreach ([[$buyer, 'Procurement & GA'], [$ga, 'Procurement & GA'], [$unknown, 'Umum'], [$kept, 'General Affair (GA)']] as [$user, $dept]) {
            Employee::create(['user_id' => $user->id, 'nik' => 'EMP-T'.$user->id, 'name' => $user->name, 'department' => $dept]);
        }

        (require database_path('migrations/master/2026_11_09_000001_fix_employee_departments_to_master_list.php'))->up();

        $dept = fn (User $u) => Employee::where('user_id', $u->id)->value('department');
        $this->assertSame('Procurement & Purchasing', $dept($buyer));
        $this->assertSame('General Affair (GA)', $dept($ga));
        $this->assertSame('Finance & Accounting', $dept($noEmployee), 'a missing employee record is created');
        $this->assertSame('Umum', $dept($unknown), 'no role to go by: left for an admin');
        $this->assertSame('General Affair (GA)', $dept($kept), 'a valid department is never overwritten');
    }

    public function test_an_inactive_user_is_logged_out_on_the_next_request(): void
    {
        $user = $this->makeUser('superadmin', [], ['status' => 'inactive']);

        $this->actingInProject($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivating_a_user_from_the_edit_form(): void
    {

        $target = $this->makeUser('staff', [], ['username' => 'joko', 'email' => 'joko@test.local']);
        $this->actingInProject($this->makeUser('superadmin'))
            ->from(route('erp.users.index'))
            ->put(route('erp.users.update', $target), [
                'name' => $target->name, 'username' => 'joko', 'email' => 'joko@test.local',
                'roles' => ['staff'], 'status' => 'inactive',
            ])
            ->assertRedirect(route('erp.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('inactive', $target->fresh()->status);
    }

    public function test_update_rejects_taking_another_users_username(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $this->makeUser('staff', [], ['username' => 'taken']);
        $target = $this->makeUser('staff');

        $this->put(route('erp.users.update', $target), [
            'name' => 'X', 'username' => 'taken', 'email' => $target->email, 'status' => 'active',
        ])->assertSessionHasErrors('username');
        $this->assertNotSame('taken', $target->fresh()->username);
    }

    public function test_delete_user_but_not_yourself(): void
    {
        $admin = $this->makeUser('superadmin');
        $target = $this->makeUser('staff');
        $this->actingInProject($admin);

        $this->deleteJson(route('erp.users.destroy', $admin))->assertStatus(422);
        $this->assertNotNull(User::find($admin->id));

        $this->deleteJson(route('erp.users.destroy', $target))->assertOk();
        $this->assertNull(User::find($target->id));
    }

    public function test_user_without_users_permission_is_refused(): void
    {
        $target = $this->makeUser('staff', [], ['name' => 'Keep']);
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.users.index'))->assertForbidden();
        $this->post(route('erp.users.store'), $this->validUser())->assertForbidden();
        $this->put(route('erp.users.update', $target), ['name' => 'Nope', 'username' => 'nope', 'email' => 'n@x.test', 'status' => 'active'])->assertForbidden();
        $this->deleteJson(route('erp.users.destroy', $target))->assertForbidden();

        $this->assertSame('Keep', $target->fresh()->name);
        $this->assertSame(0, User::where('username', 'rina')->count());
    }

    public function test_non_superadmin_cannot_grant_the_superadmin_role(): void
    {

        Role::firstOrCreate(['slug' => 'superadmin'], ['name' => 'Superadmin']);
        $manager = $this->makeUser('user_manager', ['users.view', 'users.create', 'users.update']);
        $this->actingInProject($manager);

        $this->post(route('erp.users.store'), $this->validUser(['roles' => ['superadmin']]));
        $created = User::where('username', 'rina')->first();
        $this->assertFalse((bool) $created?->hasRole('superadmin'), 'a non-superadmin created a superadmin');

        $this->put(route('erp.users.update', $manager), [
            'name' => $manager->name, 'username' => $manager->username, 'email' => $manager->email,
            'roles' => ['superadmin'], 'status' => 'active',
        ]);
        $this->assertFalse($manager->fresh()->hasRole('superadmin'), 'a non-superadmin promoted themselves');
    }
}
