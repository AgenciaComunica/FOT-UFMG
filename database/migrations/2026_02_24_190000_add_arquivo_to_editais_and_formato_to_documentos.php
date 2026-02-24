<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->string('arquivo_path')->nullable()->after('periodo_inscricao_fim');
            $table->string('arquivo_original_name')->nullable()->after('arquivo_path');
            $table->string('arquivo_mime', 120)->nullable()->after('arquivo_original_name');
            $table->unsignedBigInteger('arquivo_size')->nullable()->after('arquivo_mime');
        });

        Schema::table('edital_documento_requeridos', function (Blueprint $table) {
            $table->string('formato_aceito', 10)->default('pdf')->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('edital_documento_requeridos', function (Blueprint $table) {
            $table->dropColumn('formato_aceito');
        });

        Schema::table('editais', function (Blueprint $table) {
            $table->dropColumn(['arquivo_path', 'arquivo_original_name', 'arquivo_mime', 'arquivo_size']);
        });
    }
};
