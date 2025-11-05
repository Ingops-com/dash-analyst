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
        // First, check if PRIMARY KEY exists and drop it
        $keys = DB::select("SHOW KEYS FROM annexes WHERE Key_name = 'PRIMARY'");
        if (!empty($keys)) {
            DB::statement('ALTER TABLE annexes DROP PRIMARY KEY');
        }
        
        // Now add AUTO_INCREMENT and PRIMARY KEY
        DB::statement('ALTER TABLE annexes MODIFY id INT(11) AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove auto_increment from id
        DB::statement('ALTER TABLE annexes MODIFY id INT(11) NOT NULL');
    }
};
