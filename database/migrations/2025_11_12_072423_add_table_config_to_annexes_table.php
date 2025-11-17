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
        Schema::table('annexes', function (Blueprint $table) {
            // Agregar columnas para configuración de tablas
            $table->json('table_columns')->nullable()->after('content_type')->comment('Configuración de columnas para anexos tipo tabla');
            $table->string('table_header_color', 7)->nullable()->after('table_columns')->default('#153366')->comment('Color hexadecimal para cabeceras de tabla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            $table->dropColumn(['table_columns', 'table_header_color']);
        });
    }
};
