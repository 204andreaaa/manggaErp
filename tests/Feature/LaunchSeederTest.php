<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\Core\RoleSeeder;
use Database\Seeders\Launch\RolePermissionSeeder;
use Database\Seeders\Launch\UserRosterSeeder;
use Database\Seeders\LaunchSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\ErpTestCase;

class LaunchSeederTest extends ErpTestCase
{
    public function test_roster_creates_the_team_and_can_be_run_again_without_resetting_passwords(): void
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
            RolePermissionSeeder::for('head_project')['permissions'],
            Role::where('slug', 'head_project')->first()->permissions,
            'job roles get the standard permissions'
        );
        $this->assertSame('Project Management', $ella->employee->department);
        $this->assertTrue(Hash::check(UserRosterSeeder::SUPERADMIN_PASSWORD, User::on('master')->where('username', 'sadrak')->value('password')));
        $this->assertSame('inactive', $old->fresh()->status);
        $this->assertSame(count(UserRosterSeeder::ROSTER), User::on('master')->whereIn('username', array_keys(UserRosterSeeder::ROSTER))->count());

        // a second run keeps passwords but puts role permissions back to the standard
        $ella->update(['password' => Hash::make('ganti-sendiri')]);
        Role::where('slug', 'head_project')->update(['permissions' => json_encode(['invoices.view'])]);
        $this->seed(LaunchSeeder::class);

        $this->assertTrue(Hash::check('ganti-sendiri', $ella->fresh()->password));
        $this->assertContains('work_items.delete', Role::where('slug', 'head_project')->first()->permissions);
        $this->assertSame(1, User::on('master')->where('username', 'ella')->count());
    }

    public function test_standard_permissions_split_head_and_staff_and_keep_other_divisions_view_only(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $perm = fn (string $slug) => Role::where('slug', $slug)->first()->permissions;
        $menu = fn (string $slug) => Role::where('slug', $slug)->first()->menu_keys;

        // head vs staff
        $this->assertContains('request_forms.approve', $perm('head_procurement'));
        $this->assertNotContains('request_forms.approve', $perm('procurement'));
        $this->assertContains('suppliers.create', $perm('procurement'));
        $this->assertNotContains('suppliers.delete', $perm('procurement'));
        $this->assertContains('work_items.delete', $perm('head_project'));
        $this->assertNotContains('work_items.delete', $perm('staff_project'));

        // finance only looks at procurement / GA modules, and they are not in its menu
        $this->assertContains('payment_terms.view', $perm('finance'));
        $this->assertEmpty(array_intersect(['payment_terms.create', 'payment_terms.update', 'payment_terms.delete', 'suppliers.update'], $perm('finance')));
        $this->assertEmpty(array_intersect(['payment_terms', 'suppliers', 'goods_receipts'], $menu('finance')));
        $this->assertContains('advance_requests.pay', $perm('finance'));

        // kasbon is now reachable for the project team
        $this->assertContains('advance_requests.create', $perm('staff_project'));
    }

    public function test_view_only_finance_can_not_change_payment_terms_and_sees_no_buttons(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RolePermissionSeeder::class);
        $ririn = User::on('master')->create(['name' => 'Ririn', 'username' => 'ririn', 'email' => 'ririn@example.test', 'password' => Hash::make('x'), 'status' => 'active']);
        $ririn->roles()->sync([Role::where('slug', 'finance')->value('id')]);
        $this->project->users()->attach($ririn->id);
        $term = \App\Models\Erp\ErpPaymentTerm::create(['name' => 'Net 30', 'is_active' => true]);

        $this->actingInProject($ririn)->get(route('erp.payment-terms.index'))
            ->assertOk()->assertSee('Net 30')->assertDontSee('Create TOP');
        $this->actingInProject($ririn)->delete(route('erp.payment-terms.destroy', $term))->assertForbidden();
        $this->actingInProject($ririn)->post(route('erp.payment-terms.store'), ['name' => 'X'])->assertForbidden();
        $this->assertNotNull($term->fresh());
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
