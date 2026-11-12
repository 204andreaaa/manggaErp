<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Report templates are now private unless shared. Templates saved before that were
 * visible to everyone, so they stay shared rather than disappearing for other users.
 */
return new class extends Migration
{
    protected $connection = 'master';

    public function up(): void
    {
        DB::connection('master')->table('custom_reports')->update(['is_public' => true]);
    }

    public function down(): void
    {
        // nothing to undo safely
    }
};
