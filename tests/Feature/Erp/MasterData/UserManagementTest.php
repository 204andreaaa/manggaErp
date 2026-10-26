<?php

namespace Tests\Feature\Erp\MasterData;

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

    private function validUser(array $overrides = []): array
    {
        Role::firstOrCreate(['slug' => 'procurement'], ['name' => 'Procurement']);

        return array_merge([
            'name' => 'Rina Procurement',
            'username' => 'rina',
            'email' => 'rina@test.local',
            'phone' => '0811111',
            'position' => 'Buyer',
            'department' => 'Procurement & GA',
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
        $this->assertSame('Procurement & GA', $employee->department);
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

    public function test_an_inactive_user_is_logged_out_on_the_next_request(): void
    {
        $user = $this->makeUser('superadmin', [], ['status' => 'inactive']);

        $this->actingInProject($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_deactivating_a_user_from_the_edit_form(): void
    {
        $this->skipKnownBug("UserController::syncEmployeeProfile (line 237) copies users.status into employees.status, whose enum is active/resigned/terminated (database/migrations/master/2026_09_04_000001_create_employees_table.php:54): saving a user as 'inactive' (create or update) throws 'Data truncated' -> HTTP 500, so users cannot be deactivated.");

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
        $this->skipKnownBug("UserController::store/update (lines 59-60 / 145-146) only check 'roles.*' => exists:master.roles,slug, so a user manager who is not superadmin can create a superadmin or promote themselves.");

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
