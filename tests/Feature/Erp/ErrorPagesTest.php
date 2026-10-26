<?php

namespace Tests\Feature\Erp;

use Illuminate\Support\Facades\Route;
use Tests\ErpTestCase;

class ErrorPagesTest extends ErpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);

        Route::middleware('web')->group(function () {
            Route::get('/__test/forbidden-with-reason', fn () => abort(403, 'Hanya Finance yang boleh membayar kasbon.'));
            Route::get('/__test/boom', fn () => throw new \RuntimeException('SQLSTATE secret table erp_users'));
        });
    }

    public function test_unknown_page_shows_the_404_banner(): void
    {
        $this->actingInProject($this->makeUser('superadmin'))
            ->get('/erp/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Ke Dashboard');
    }

    public function test_missing_permission_shows_the_403_banner(): void
    {
        $this->actingInProject($this->makeUser('staff'))
            ->get(route('erp.payment-terms.index'))
            ->assertForbidden()
            ->assertSee('Akses Ditolak');
    }

    public function test_403_banner_shows_the_reason_given_by_the_app(): void
    {
        $this->get('/__test/forbidden-with-reason')
            ->assertForbidden()
            ->assertSee('Hanya Finance yang boleh membayar kasbon.');
    }

    public function test_500_banner_never_leaks_the_exception_text(): void
    {
        $this->get('/__test/boom')
            ->assertStatus(500)
            ->assertSee('Terjadi Kesalahan di Sistem')
            ->assertDontSee('SQLSTATE');
    }

    public function test_ajax_requests_still_get_json_not_the_banner(): void
    {
        $this->actingInProject($this->makeUser('staff'))
            ->getJson(route('erp.payment-terms.index'))
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }
}
