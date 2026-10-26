<?php

namespace Tests\Feature\Erp\MasterData;

use App\Models\Role;
use Tests\ErpTestCase;

class RoleTest extends ErpTestCase
{
    public function test_list_page_shows_roles(): void
    {
        Role::create(['slug' => 'site_engineer', 'name' => 'Site Engineer Role']);
        $this->actingInProject($this->makeUser('superadmin'));

        $this->get(route('erp.roles.index'))->assertOk()->assertSee('Site Engineer Role');
    }

    public function test_create_role_with_menus_and_permissions(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        $this->from(route('erp.roles.index'))->post(route('erp.roles.store'), [
            'slug' => 'buyer',
            'name' => 'Buyer',
            'menu_keys' => ['products', 'suppliers'],
            'permissions' => ['products.view', 'products.create'],
        ])->assertRedirect(route('erp.roles.index'))->assertSessionHasNoErrors();

        $role = Role::where('slug', 'buyer')->firstOrFail();
        $this->assertSame(['products', 'suppliers'], $role->menu_keys);
        $this->assertSame(['products.view', 'products.create'], $role->permissions);
        // No home page picked: the first menu's route is used.
        $this->assertSame('erp.products.index', $role->home_route);

        // The role works: a user holding it may create products.
        $buyer = $this->makeUser('buyer');
        $this->assertTrue($buyer->hasPermission('products.create'));
        $this->assertFalse($buyer->hasPermission('products.delete'));
    }

    public function test_create_rejects_duplicate_slug_and_unknown_menu_or_home(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        Role::create(['slug' => 'buyer', 'name' => 'Buyer']);

        $this->post(route('erp.roles.store'), ['slug' => 'buyer', 'name' => 'Again', 'menu_keys' => ['products']])
            ->assertSessionHasErrors('slug');
        $this->post(route('erp.roles.store'), ['slug' => 'bad slug!', 'name' => 'X', 'menu_keys' => ['nope'], 'home_route' => 'logout'])
            ->assertSessionHasErrors(['slug', 'menu_keys.0', 'home_route']);
        $this->post(route('erp.roles.store'), ['slug' => 'empty', 'name' => 'X', 'menu_keys' => []])
            ->assertSessionHasErrors('menu_keys');

        $this->assertSame(['buyer'], Role::whereIn('slug', ['buyer', 'empty', 'bad slug!'])->pluck('slug')->all());
        $this->assertSame('Buyer', Role::where('slug', 'buyer')->value('name'));
    }

    public function test_update_and_delete(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));
        $role = Role::create(['slug' => 'buyer', 'name' => 'Buyer', 'menu_keys' => ['products'], 'permissions' => ['products.view']]);
        Role::create(['slug' => 'taken', 'name' => 'Taken']);

        $this->put(route('erp.roles.update', $role), ['slug' => 'taken', 'name' => 'X', 'menu_keys' => ['products']])
            ->assertSessionHasErrors('slug');

        $this->put(route('erp.roles.update', $role), [
            'slug' => 'senior_buyer', 'name' => 'Senior Buyer', 'menu_keys' => ['suppliers'],
            'home_route' => 'dashboard',
        ])->assertSessionHasNoErrors();

        $role->refresh();
        $this->assertSame('senior_buyer', $role->slug);
        $this->assertSame('dashboard', $role->home_route);
        $this->assertSame(['suppliers'], $role->menu_keys);
        $this->assertSame([], $role->permissions, 'unticking every permission clears them');

        $this->deleteJson(route('erp.roles.destroy', $role))->assertOk()->assertJson(['success' => true]);
        $this->assertNull(Role::find($role->id));
    }

    public function test_user_without_roles_permission_is_refused(): void
    {
        $role = Role::create(['slug' => 'buyer', 'name' => 'Keep']);
        $this->actingInProject($this->makeUser('staff', ['users.view']));

        $this->get(route('erp.roles.index'))->assertForbidden();
        $this->post(route('erp.roles.store'), ['slug' => 'nope', 'name' => 'Nope', 'menu_keys' => ['products']])->assertForbidden();
        $this->put(route('erp.roles.update', $role), ['slug' => 'buyer', 'name' => 'Nope', 'menu_keys' => ['products']])->assertForbidden();
        $this->deleteJson(route('erp.roles.destroy', $role))->assertForbidden();

        $this->assertSame('Keep', $role->fresh()->name);
        $this->assertNull(Role::where('slug', 'nope')->first());
    }
}
