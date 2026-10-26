<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests wipe and rebuild their databases. Refuse to run against anything
        // that isn't a dedicated test database (see phpunit.xml).
        foreach (['master', 'tenant'] as $connection) {
            $database = (string) config("database.connections.{$connection}.database");
            if (!str_starts_with($database, 'mangga_test_')) {
                $this->fail("Tes dihentikan: koneksi '{$connection}' menunjuk ke database '{$database}', bukan database tes (mangga_test_*).");
            }
        }
    }
}
