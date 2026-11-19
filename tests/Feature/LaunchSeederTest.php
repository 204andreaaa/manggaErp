<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\Launch\UserRosterSeeder;
use Database\Seeders\LaunchSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\ErpTestCase;

class LaunchSeederTest extends ErpTestCase
{
    public function test_roster_creates_the_team_and_can_be_run_again_without_resetting_anything(): void
    {
        $this->seed(RoleSeeder::class);
        $old = User::on('master')->create([
            'name' => 'Budi', 'username' => 'budi', 'email' => 'budi@example.test', 'password' => Hash::make('x'), 'status' => 'active',
        ]);

        $this->seed(LaunchSeeder::class);

        $ella = User::on('master')->where('username', 'ella')->firstOrFail();
        $this->assertTrue(Hash::check(UserRosterSeeder::STAFF_PASSWORD, $ella->password));
        $this->assertSame(['head_project'], $ella->roles->pluck('slug')->all());
        $this->assertTrue($ella->hasRole('admin_project'), 'a project job role counts as the project division');
        $this->assertFalse($ella->hasRole('procurement'));
        $this->assertEqualsCanonicalizing(
            Role::where('slug', 'admin_project')->first()->permissions,
            Role::where('slug', 'head_project')->first()->permissions,
            'a new job role starts as a copy of its division role'
        );
        $this->assertSame('Project Management', $ella->employee->department);
        $this->assertTrue(Hash::check(UserRosterSeeder::SUPERADMIN_PASSWORD, User::on('master')->where('username', 'sadrak')->value('password')));
        $this->assertSame('inactive', $old->fresh()->status);
        $this->assertSame(count(UserRosterSeeder::ROSTER), User::on('master')->whereIn('username', array_keys(UserRosterSeeder::ROSTER))->count());

        // changes made in the app survive a second run
        $ella->update(['password' => Hash::make('ganti-sendiri')]);
        Role::where('slug', 'head_project')->update(['permissions' => json_encode(['invoices.view'])]);
        $this->seed(LaunchSeeder::class);

        $this->assertTrue(Hash::check('ganti-sendiri', $ella->fresh()->password));
        $this->assertSame(['invoices.view'], Role::where('slug', 'head_project')->first()->permissions);
        $this->assertSame(1, User::on('master')->where('username', 'ella')->count());
    }

    public function test_transfer_hands_everything_to_the_replacement_and_deletes_the_old_account(): void
    {
        $this->seed(RoleSeeder::class);
        $old = User::on('master')->create(['name' => 'Budi', 'username' => 'budi', 'email' => 'budi@example.test', 'password' => Hash::make('x'), 'status' => 'inactive']);
        $new = User::on('master')->create(['name' => 'Sesa', 'username' => 'sesa', 'email' => 'sesa@example.test', 'password' => Hash::make('x'), 'status' => 'active']);
        $configId = \Illuminate\Support\Facades\DB::connection('tenant')->table('erp_approval_configs')->insertGetId([
            'record_type' => 'advance_request', 'level' => 1, 'name' => 'KAM Approval', 'user_id' => $old->id,
        ]);

        $this->artisan('users:transfer', ['from' => 'budi', 'to' => 'sesa', '--delete' => true])->assertSuccessful();
        $this->assertNotNull($old->fresh(), 'without --execute nothing changes');

        $this->artisan('users:transfer', ['from' => 'budi', 'to' => 'sesa', '--delete' => true, '--execute' => true])->assertSuccessful();
        $this->assertNull($old->fresh());
        $this->assertSame($new->id, (int) \Illuminate\Support\Facades\DB::connection('tenant')->table('erp_approval_configs')->where('id', $configId)->value('user_id'));
    }
}
