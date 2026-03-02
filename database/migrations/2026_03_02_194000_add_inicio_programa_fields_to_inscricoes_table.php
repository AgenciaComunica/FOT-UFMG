<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('inscricoes', 'inicio_programa_semestre')) {
                $table->unsignedTinyInteger('inicio_programa_semestre')->nullable()->after('telefone');
            }
            if (! Schema::hasColumn('inscricoes', 'inicio_programa_ano')) {
                $table->unsignedSmallInteger('inicio_programa_ano')->nullable()->after('inicio_programa_semestre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table): void {
            if (Schema::hasColumn('inscricoes', 'inicio_programa_ano')) {
                $table->dropColumn('inicio_programa_ano');
            }
            if (Schema::hasColumn('inscricoes', 'inicio_programa_semestre')) {
                $table->dropColumn('inicio_programa_semestre');
            }
        });
    }
};

