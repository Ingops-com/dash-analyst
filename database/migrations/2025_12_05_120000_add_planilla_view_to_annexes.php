<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            $table->string('planilla_view', 100)->nullable()->after('content_type');
        });
    }

    public function down(): void
    {
        Schema::table('annexes', function (Blueprint $table) {
            $table->dropColumn('planilla_view');
        });
    }
};
