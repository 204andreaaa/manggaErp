<?php

namespace Tests\Feature\Erp;

use App\Models\CustomReport;
use Tests\ErpTestCase;

/**
 * A saved report template is private to its maker unless shared with everyone;
 * only the maker (or a superadmin) can delete it.
 */
class CustomReportTemplateTest extends ErpTestCase
{
    private function save(array $overrides = []): void
    {
        $this->postJson(route('erp.reports.store'), array_merge([
            'title' => 'Rekap PO', 'report_type' => 'purchase_orders', 'selected_columns' => ['po_no'],
        ], $overrides))->assertOk()->assertJson(['success' => true]);
    }

    public function test_templates_are_private_unless_shared(): void
    {
        $maker = $this->makeUser('analyst', ['custom_reports.view']);
        $colleague = $this->makeUser('analyst', ['custom_reports.view']);

        $this->actingInProject($maker);
        $this->save(['title' => 'Punya Saya']);
        $this->save(['title' => 'Untuk Semua', 'is_public' => true]);
        $private = CustomReport::where('title', 'Punya Saya')->firstOrFail();
        $this->assertFalse($private->is_public);

        $this->get(route('erp.reports.index'))->assertOk()->assertSee('Punya Saya')->assertSee('Untuk Semua');

        $this->actingInProject($colleague)
            ->get(route('erp.reports.index'))->assertOk()->assertDontSee('Punya Saya')->assertSee('Untuk Semua');
        $this->get(route('erp.reports.builder', ['report_id' => $private->id]))->assertOk()->assertDontSee('Punya Saya');
    }

    public function test_only_the_maker_or_a_superadmin_deletes_a_shared_template(): void
    {
        $maker = $this->makeUser('analyst', ['custom_reports.view']);
        $this->actingInProject($maker);
        $this->save(['is_public' => true]);
        $template = CustomReport::sole();

        $this->actingInProject($this->makeUser('analyst', ['custom_reports.view']))
            ->deleteJson(route('erp.reports.destroy', $template))->assertForbidden();
        $this->assertNotNull($template->fresh());

        $this->actingInProject($maker)->deleteJson(route('erp.reports.destroy', $template))->assertOk();
        $this->assertNull($template->fresh());
    }
}
