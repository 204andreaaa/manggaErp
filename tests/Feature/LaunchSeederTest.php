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
        $this->assertEqualsCanonicalizing(['admin_project', 'head_project'], $ella->roles->pluck('slug')->all());
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
}
