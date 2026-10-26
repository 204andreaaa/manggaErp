<?php

namespace Tests\Feature\Erp\RequestForm;

use App\Models\Erp\RequestForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ErpTestCase;

class RequestFormCrudTest extends ErpTestCase
{
    use RequestFormFixtures;

    public function test_create_pages_render_for_both_record_types_and_reject_unknown_type(): void
    {
        $this->makeWorkItem();
        $this->actingInProject($this->makeUser('staff'));

        $this->get(route('erp.request-form.create', ['type' => 'project']))->assertOk()->assertSee('Project Based');
        $this->get(route('erp.request-form.create', ['type' => 'non_project']))->assertOk()->assertSee('Non Project Based');
        $this->get(route('erp.request-form.create', ['type' => 'bogus']))->assertNotFound();
    }

    public function test_staff_creates_a_non_project_rf_with_line_items_notes_and_attachment(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('staff');

        $payload = $this->rfPayload('non_project', [
            $this->itemPayload(['product_name' => 'Item A', 'unit_cost' => 10000, 'qty' => 5]),
            $this->itemPayload(['product_name' => 'Item B', 'unit_cost' => 2500, 'qty' => 2, 'actual_cost' => 3000]),
        ], [
            'project_code' => 'IGNORED-FOR-NON-PROJECT',
            'notes' => ['first note', '   '],
            'attachments' => [UploadedFile::fake()->create('quote.pdf', 20, 'application/pdf')],
        ]);

        $response = $this->actingInProject($user)->post(route('erp.request-form.store'), $payload);

        $rf = RequestForm::with(['items', 'notesAttachments'])->latest('id')->firstOrFail();
        $response->assertRedirect(route('erp.request-form.show', $rf))->assertSessionHas('success');

        $this->assertSame('Draft', $rf->status);
        $this->assertSame('non_project', $rf->record_type);
        $this->assertNull($rf->project_code, 'non-project RFs never keep a project code');
        $this->assertSame($user->id, (int) $rf->created_by_id);
        $this->assertSame($user->name, $rf->requestor, 'requestor defaults to the logged-in user');
        $this->assertMatchesRegularExpression('/^RF-\d{4}-\d{2}-00001$/', $rf->rf_no);
        $this->assertTrue($rf->expense_material_equipment);
        $this->assertFalse($rf->expense_office);

        // 5 x 10,000 + 2 x 3,000 (actual cost overrides unit cost)
        $this->assertSame(56000, $rf->total_amount);
        $this->assertCount(2, $rf->items);
        $b = $rf->items->firstWhere('product_name', 'Item B');
        $this->assertSame(3000, $b->unit_cost);
        $this->assertSame(6000, $b->original_total_cost);
        $this->assertSame('Requested', $b->status);
        $this->assertEqualsCanonicalizing(
            ['RFIN-'.substr($rf->rf_no, 3).'-001', 'RFIN-'.substr($rf->rf_no, 3).'-002'],
            $rf->items->pluck('rf_detail_no')->all()
        );

        $this->assertSame(['first note'], $rf->notesAttachments->where('type', 'note')->pluck('content')->values()->all());
        $attachment = $rf->notesAttachments->firstWhere('type', 'attachment');
        $this->assertSame('quote.pdf', $attachment->file_name);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_project_rf_keeps_project_code_and_item_wid(): void
    {
        $wid = $this->makeWorkItem();
        $user = $this->makeUser('staff');

        $rf = $this->createRfViaHttp($user, 'project', [$this->itemPayload(['wid' => $wid->wid_code])], [
            'project_code' => $wid->subProject->sub_project_code,
        ]);

        $this->assertSame('project', $rf->record_type);
        $this->assertSame($wid->subProject->sub_project_code, $rf->project_code);
        $this->assertSame($wid->wid_code, $rf->items()->first()->wid);
        $this->assertSame(50000, $rf->total_amount);

        $this->get(route('erp.request-form.show', $rf))->assertOk()->assertSee($rf->rf_no)->assertSee($wid->wid_code);
        $this->get(route('erp.request-form-items.show', $rf->items()->first()))->assertOk();
    }

    public function test_rf_numbers_increment(): void
    {
        $user = $this->makeUser('staff');
        $first = $this->createRfViaHttp($user);
        $second = $this->createRfViaHttp($user);

        $this->assertStringEndsWith('-00001', $first->rf_no);
        $this->assertStringEndsWith('-00002', $second->rf_no);
    }

    public function test_store_rejects_missing_required_fields(): void
    {
        $this->actingInProject($this->makeUser('staff'));

        $this->post(route('erp.request-form.store'), [
            'record_type' => 'project',
            'items' => [['product_name' => '', 'qty' => 0, 'status' => 'Weird']],
        ])->assertSessionHasErrors([
            'project_code', 'priority', 'rf_date',
            'items.0.product_name', 'items.0.currency', 'items.0.unit_cost', 'items.0.qty', 'items.0.status',
        ]);

        $this->post(route('erp.request-form.store'), $this->rfPayload('neither'))
            ->assertSessionHasErrors('record_type');

        $this->assertSame(0, RequestForm::count());
    }

    public function test_project_rf_item_without_wid_is_rejected(): void
    {

        $this->actingInProject($this->makeUser('staff'))
            ->post(route('erp.request-form.store'), $this->rfPayload('project', [$this->itemPayload(['wid' => null])]))
            ->assertSessionHasErrors('items.0.wid');
        $this->assertSame(0, RequestForm::count());
    }

    public function test_draft_rf_can_be_edited_items_updated_added_and_removed(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user, 'non_project', [
            $this->itemPayload(['product_name' => 'Keep']),
            $this->itemPayload(['product_name' => 'Drop']),
        ], ['notes' => ['old note']]);
        $keep = $rf->items()->where('product_name', 'Keep')->first();

        $this->get(route('erp.request-form.edit', $rf))->assertOk();

        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', [
            $this->itemPayload(['id' => $keep->id, 'product_name' => 'Keep', 'qty' => 10]),
            $this->itemPayload(['product_name' => 'New', 'unit_cost' => 1000, 'qty' => 3]),
        ], ['priority' => 'Urgent', 'notes' => ['new note']]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('erp.request-form.show', $rf));

        $rf->refresh();
        $this->assertSame('Urgent', $rf->priority);
        $this->assertSame('Draft', $rf->status);
        $this->assertSame(['Keep', 'New'], $rf->items()->orderBy('id')->pluck('product_name')->all());
        $this->assertSame('10.00', $keep->fresh()->qty);
        $this->assertSame(100000 + 3000, $rf->total_amount);
        $this->assertSame(['new note'], $rf->notesAttachments()->where('type', 'note')->pluck('content')->all());
    }

    public function test_new_line_after_removing_one_gets_a_unique_detail_number(): void
    {

        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user, 'non_project', [
            $this->itemPayload(['product_name' => 'A']),
            $this->itemPayload(['product_name' => 'B']),
        ]);
        $b = $rf->items()->where('product_name', 'B')->first();

        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', [
            $this->itemPayload(['id' => $b->id, 'product_name' => 'B']),
            $this->itemPayload(['product_name' => 'C']),
        ]))->assertSessionHasNoErrors();

        $numbers = $rf->items()->pluck('rf_detail_no')->all();
        $this->assertSame(count($numbers), count(array_unique($numbers)), 'rf_detail_no must be unique: '.implode(', ', $numbers));
    }

    public function test_update_validates_like_store(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);

        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', null, ['rf_date' => 'not-a-date', 'priority' => null]))
            ->assertSessionHasErrors(['rf_date', 'priority']);
        $this->assertSame('Normal', $rf->fresh()->priority);
    }

    public function test_rf_that_is_no_longer_draft_cannot_be_edited(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);
        $rf->update(['status' => 'Submitted']);

        $this->get(route('erp.request-form.edit', $rf))->assertForbidden();
        $this->put(route('erp.request-form.update', $rf), $this->rfPayload('non_project', null, ['priority' => 'Urgent']))
            ->assertForbidden();
        $this->assertSame('Normal', $rf->fresh()->priority);
    }

    public function test_clone_form_prefills_from_an_existing_rf_without_saving(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user, 'non_project', [$this->itemPayload(['product_name' => 'Clone Me'])]);

        $this->get(route('erp.request-form.clone', $rf))->assertOk()->assertSee('Clone Me');
        $this->assertSame(1, RequestForm::count());
    }

    public function test_notes_and_attachments_can_be_added_from_the_detail_page(): void
    {
        Storage::fake('public');
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);

        $this->post(route('erp.request-form.notes.store', $rf), ['content' => 'Follow up'])->assertSessionHas('success');
        $this->post(route('erp.request-form.notes.store', $rf), ['content' => ''])->assertSessionHasErrors('content');
        $this->post(route('erp.request-form.attachments.store', $rf), ['file' => UploadedFile::fake()->create('virus.exe', 5)])
            ->assertSessionHasErrors('file');
        $this->post(route('erp.request-form.attachments.store', $rf), ['file' => UploadedFile::fake()->image('photo.jpg')])
            ->assertSessionHas('success');

        $this->assertSame(['Follow up'], $rf->notesAttachments()->where('type', 'note')->pluck('content')->all());
        $attachment = $rf->notesAttachments()->where('type', 'attachment')->firstOrFail();
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_index_datatable_lists_rfs(): void
    {
        $user = $this->makeUser('staff');
        $rf = $this->createRfViaHttp($user);

        $this->get(route('erp.request-form.index'))->assertOk();
        $this->post(route('erp.request-form.datatable'), ['draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => $rf->rf_no]])
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.items_count', 1);
    }
}
