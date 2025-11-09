<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_program_annex_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('annex_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'program_id', 'annex_id'], 'uniq_company_program_annex');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_program_annex_configs');
    }
};
