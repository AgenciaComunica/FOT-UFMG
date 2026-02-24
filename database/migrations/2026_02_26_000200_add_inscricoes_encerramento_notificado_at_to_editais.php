<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->dateTime('inscricoes_encerramento_notificado_at')->nullable()->after('periodo_inscricao_fim');
            $table->index('inscricoes_encerramento_notificado_at');
        });
    }

    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->dropIndex(['inscricoes_encerramento_notificado_at']);
            $table->dropColumn('inscricoes_encerramento_notificado_at');
        });
    }
};

