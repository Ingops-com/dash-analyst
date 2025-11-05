<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('template_path')->nullable()->after('tipo')
                  ->comment('Ruta relativa de la plantilla Word en storage/plantillas/');
            $table->text('description')->nullable()->after('template_path')
                  ->comment('Descripción del programa/documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['template_path', 'description']);
        });
    }
};
