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
        /**
         * Tabla pivote company_user
         * - Relación muchos a muchos entre empresas y usuarios
         * - No toca la tabla users ni la tabla companies existentes
         */
        if (!Schema::hasTable('company_user')) {
            Schema::create('company_user', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id'); // Referencia lógica a companies.id
                $table->integer('user_id');    // Referencia lógica a users.id
                $table->timestamps();

                $table->unique(['company_id', 'user_id'], 'company_user_company_id_user_id_unique');
                $table->index('user_id', 'company_user_user_id_index');
            });
        }

        /**
         * Tabla pivote company_programs
         * - Relación muchos a muchos entre empresas y programas
         * - No toca la tabla companies ni la tabla programs existentes
         */
        if (!Schema::hasTable('company_programs')) {
            Schema::create('company_programs', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id');  // Referencia lógica a companies.id
                $table->integer('program_id');  // Referencia lógica a programs.id
                $table->timestamps();

                $table->unique(['company_id', 'program_id'], 'company_programs_company_id_program_id_unique');
                $table->index('program_id', 'company_programs_program_id_index');
            });
        }

        /**
         * Opcional: aquí podríamos agregar índices a program_annexes,
         * pero para NO arriesgar errores en producción y NO afectar nada,
         * no tocamos esa tabla desde esta migración.
         */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // En rollback solo eliminamos las tablas nuevas (si existen)
        if (Schema::hasTable('company_user')) {
            Schema::dropIfExists('company_user');
        }

        if (Schema::hasTable('company_programs')) {
            Schema::dropIfExists('company_programs');
        }
    }
};
