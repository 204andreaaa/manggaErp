<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\RequestForm;
use Illuminate\Testing\TestResponse;
use Tests\ErpTestCase;

/**
 * The RF edit page must load the RF's existing lines into its line-items table
 * (and post them back with their ids), otherwise saving fails with
 * "Barang Belum Ditambahkan" and a rejected RF can't be fixed by its owner.
 */
class RequestFormEditPageTest extends ErpTestCase
{
    use RequestFormFixtures;

    private function rfWithTwoLines($user): RequestForm
    {
        return $this->createRfViaHttp($user, 'non_project', [
            $this->itemPayload(['product_name' => 'Kabel Alpha', 'qty' => 5, 'unit_cost' => 10000]),
            $this->itemPayload(['product_name' => 'Kabel Beta', 'qty' => 2, 'unit_cost' => 2500]),
        ])->fresh('items');
    }

    /** The array the page script receives as `initialItems`. */
    private function initialItems(TestResponse $response): array
    {
        $this->assertSame(1, preg_match('/const initialItems = (.*?);\n/', $response->getContent(), $m), 'initialItems is rendered');

        return json_decode($m[1], true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_edit_page_feeds_the_existing_lines_into_the_table(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->rfWithTwoLines($user);

        $response = $this->actingInProject($user)->get(route('erp.request-form.edit', $rf))->assertOk();

        $items = $this->initialItems($response);
        $this->assertCount(2, $items);
        $this->assertEqualsCanonicalizing($rf->items->pluck('id')->all(), array_column($items, 'id'));
        $this->assertEqualsCanonicalizing(['Kabel Alpha', 'Kabel Beta'], array_column($items, 'product_name'));
        // a plain Y-m-d, not a UTC timestamp that would shift the day
        $this->assertSame($rf->items[0]->date_required->toDateString(), $items[0]['date_required']);

        // the script builds lineItems from initialItems (keeping each line's id) and renders it on load
        $html = $response->getContent();
        $this->assertStringNotContainsString('const lineItems = [];', $html);
        $this->assertStringContainsString('const lineItems = (Array.isArray(initialItems)', $html);
        $this->assertStringContainsString('if (item.id) line.id = String(item.id);', $html);
        $this->assertMatchesRegularExpression('/renderHiddenInputs\(\);\s*\}\s*renderTable\(\);/', $html);
    }

    public function test_rejected_rf_edit_page_also_loads_its_lines(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->rfWithTwoLines($user);
        $rf->update(['status' => 'Rejected']);

        $response = $this->actingInProject($user)->get(route('erp.request-form.edit', $rf))->assertOk();

        $this->assertCount(2, $this->initialItems($response));
    }

    public function test_saving_the_lines_as_the_page_posts_them_keeps_their_ids(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->rfWithTwoLines($user);
        $before = $rf->items->sortBy('id')->values();

        $items = $this->initialItems($this->actingInProject($user)->get(route('erp.request-form.edit', $rf)));

        // same normalization as the page script, plus one edit and one new line
        $posted = array_map(fn (array $item) => [
            'id' => (string) $item['id'],
            'product_name' => $item['product_name'],
            'product_id_text' => $item['product_id_text'] ?? '',
            'wid' => $item['wid'] ?? '',
            'currency' => $item['currency'],
            'status' => $item['status'],
            'qty' => (string) $item['qty'],
            'qty_fulfilled' => (string) $item['qty_fulfilled'],
            'unit_cost' => (string) $item['unit_cost'],
            'original_total_cost' => (string) $item['original_total_cost'],
            'actual_cost' => (string) $item['actual_cost'],
            'date_required' => substr((string) $item['date_required'], 0, 10),
            'pic' => $item['pic'] ?? '',
            'within_budget' => $item['within_budget'] ? '1' : '0',
            'remark' => $item['remark'] ?? '',
        ], $items);
        $alpha = array_search('Kabel Alpha', array_column($posted, 'product_name'), true);
        $posted[$alpha]['qty'] = '7';
        $posted[] = $this->itemPayload(['product_name' => 'Kabel Gamma', 'qty' => 1, 'unit_cost' => 1000]);

        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', $posted))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $after = $rf->fresh('items')->items->sortBy('id')->values();
        $this->assertCount(3, $after);
        foreach ($before as $i => $old) {
            $this->assertSame($old->id, $after[$i]->id, 'existing line kept, not recreated');
            $this->assertSame($old->rf_detail_no, $after[$i]->rf_detail_no);
            $this->assertSame($old->date_required->toDateString(), $after[$i]->date_required->toDateString());
        }
        $this->assertEquals(7, $after->firstWhere('product_name', 'Kabel Alpha')->qty);
        $this->assertSame('Kabel Gamma', $after[2]->product_name);
    }

    public function test_old_input_wins_after_a_validation_error(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->rfWithTwoLines($user);
        $alpha = $rf->items->firstWhere('product_name', 'Kabel Alpha');

        $response = $this->actingInProject($user)
            ->from(route('erp.request-form.edit', $rf))
            ->followingRedirects()
            ->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', [
                $this->itemPayload(['id' => $alpha->id, 'product_name' => 'Kabel Alpha Diubah']),
            ], ['rf_date' => 'not-a-date']))
            ->assertOk();

        $items = $this->initialItems($response);
        $this->assertCount(1, $items);
        $this->assertSame('Kabel Alpha Diubah', $items[0]['product_name']);
        $this->assertEquals($alpha->id, $items[0]['id']);
        $this->assertCount(2, $rf->fresh('items')->items, 'nothing saved');
    }
}
