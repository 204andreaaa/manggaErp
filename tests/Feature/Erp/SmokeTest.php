<?php

namespace Tests\Feature\Erp;

use Tests\ErpTestCase;

class SmokeTest extends ErpTestCase
{
    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_user_without_a_project_is_asked_to_pick_one(): void
    {
        $this->actingAs($this->makeUser('superadmin'))
            ->get(route('erp.request-form.index'))
            ->assertRedirect(route('projects.switch'));
    }

    public function test_superadmin_opens_the_main_pages_inside_a_project(): void
    {
        $this->actingInProject($this->makeUser('superadmin'));

        foreach (['dashboard', 'erp.request-form.index', 'erp.purchase-orders.index', 'erp.work-items.index', 'erp.annual-budget-plans.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_each_test_starts_from_a_clean_database(): void
    {
        // The project created in setUp is the only one: earlier tests were rolled back.
        $this->assertSame(1, \App\Models\Project::count());
    }
}
