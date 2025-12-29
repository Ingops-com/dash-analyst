<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar 'planilla' al enum de content_type en la tabla annexes
        DB::statement("ALTER TABLE annexes MODIFY content_type ENUM('image', 'text', 'table', 'pdf', 'planilla') DEFAULT 'image'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores anteriores sin 'planilla'
        DB::statement("ALTER TABLE annexes MODIFY content_type ENUM('image', 'text', 'table', 'pdf') DEFAULT 'image'");
    }
};
