<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            if (!Schema::hasColumn('annexes', 'placeholder')) {
                $table->string('placeholder', 255)->nullable()->after('codigo_anexo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            if (Schema::hasColumn('annexes', 'placeholder')) {
                $table->dropColumn('placeholder');
            }
        });
    }
};
