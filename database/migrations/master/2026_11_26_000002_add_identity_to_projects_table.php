<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each project (tenant) is one PT: its legal identity is the letterhead of the PO, GR and
 * other printouts. Until now that letterhead was written into the views as Mandau, so the
 * existing projects start with the Mandau identity (an "imprima" project with its own name).
 */
return new class extends Migration
{
    protected $connection = 'master';

    private const FIELDS = ['legal_name', 'address', 'phone', 'fax', 'email', 'website', 'tax_number', 'logo_path'];

    public function up(): void
    {
        Schema::connection('master')->table('projects', function (Blueprint $table) {
            foreach (self::FIELDS as $field) {
                if (Schema::connection('master')->hasColumn('projects', $field)) {
                    continue;
                }
                $field === 'address' ? $table->text($field)->nullable() : $table->string($field)->nullable();
            }
        });

        $mandau = [
            'legal_name' => 'PT Mandiri Daya Utama Nusantara',
            'address' => "Komp Golden Plaza Jl. RS Fatmawati No.15, Blok C 16 - 17\nJakarta Selatan - 12420",
            'phone' => '+6221-7599 7945',
            'fax' => '+6221-7599 9888',
            'email' => 'info@mandau.id',
            'website' => 'https://mandau.id',
            'logo_path' => 'ImageAsset/logo-mandau.png',
        ];

        foreach (DB::connection('master')->table('projects')->whereNull('legal_name')->get(['id', 'name']) as $project) {
            $identity = str_contains(strtolower($project->name), 'imprima')
                ? ['legal_name' => 'PT Intertel Media Prima']
                : $mandau;
            DB::connection('master')->table('projects')->where('id', $project->id)->update($identity);
        }
    }

    public function down(): void
    {
        Schema::connection('master')->table('projects', function (Blueprint $table) {
            $table->dropColumn(self::FIELDS);
        });
    }
};
