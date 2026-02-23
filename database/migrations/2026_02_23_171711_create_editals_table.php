<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editais', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('periodo_inscricao_inicio');
            $table->dateTime('periodo_inscricao_fim');
            $table->timestamps();

            $table->index(['periodo_inscricao_inicio', 'periodo_inscricao_fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editais');
    }
};
