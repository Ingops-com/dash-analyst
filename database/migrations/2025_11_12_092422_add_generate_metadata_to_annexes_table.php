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
            $table->boolean('generate_metadata')->default(true)->after('table_header_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            $table->dropColumn('generate_metadata');
        });
    }
};
