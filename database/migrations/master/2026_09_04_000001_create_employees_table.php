<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel users (Nullable: jika karyawan tidak butuh login sistem)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Identitas Karyawan (HRIS)
            $table->string('nik', 50)->nullable()->unique(); // NIP / NIK Karyawan (misal: EMP-2026-001)
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 25)->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('place_of_birth', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Dokumen Resmi & Legalitas
            $table->string('id_card_number', 30)->nullable(); // No KTP
            $table->string('tax_id', 30)->nullable();        // NPWP
            $table->string('bpjs_tk', 30)->nullable();       // BPJS Ketenagakerjaan
            $table->string('bpjs_kes', 30)->nullable();      // BPJS Kesehatan
            
            // Rekening Penggajian (Payroll)
            $table->string('bank_name', 50)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_holder', 100)->nullable();
            
            // Domisili & Kontak Darurat
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 25)->nullable();
            
            // Penempatan & Status Kerja
            $table->string('department', 100)->nullable();   // Divisi: Project, Procurement, GA, Finance, IT, dll
            $table->string('position', 100)->nullable();     // Jabatan
            $table->enum('employment_status', ['permanent', 'contract', 'internship', 'freelance', 'probation'])->default('permanent');
            $table->date('join_date')->nullable();
            $table->date('end_contract_date')->nullable();
            
            // Penggajian & Berkas
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();
            
            // Status Karyawan
            $table->enum('status', ['active', 'resigned', 'terminated'])->default('active');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
