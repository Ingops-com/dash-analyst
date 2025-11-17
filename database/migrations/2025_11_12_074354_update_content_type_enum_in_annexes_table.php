<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            // Drop existing enum and recreate with new values
            DB::statement("ALTER TABLE annexes MODIFY content_type ENUM('image', 'text', 'table') DEFAULT 'image'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE annexes MODIFY content_type ENUM('image', 'text') DEFAULT 'image'");
        });
    }
};
