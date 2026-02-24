<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricao_avaliacoes', function (Blueprint $table) {
            $table->string('avaliacao_subjetiva', 20)->nullable()->after('nota');
            $table->index('avaliacao_subjetiva');
        });
    }

    public function down(): void
    {
        Schema::table('inscricao_avaliacoes', function (Blueprint $table) {
            $table->dropIndex(['avaliacao_subjetiva']);
            $table->dropColumn('avaliacao_subjetiva');
        });
    }
};
