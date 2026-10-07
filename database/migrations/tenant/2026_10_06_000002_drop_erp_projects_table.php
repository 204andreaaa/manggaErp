<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('erp_projects');
    }

    public function down(): void
    {
        Schema::create('erp_projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_code')->unique();
            $table->string('name')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
