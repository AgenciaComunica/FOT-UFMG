<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscricao_avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('inscricoes')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('nota', 4, 2)->nullable();
            $table->text('comentario')->nullable();
            $table->dateTime('avaliado_at')->nullable();
            $table->timestamps();

            $table->unique(['inscricao_id', 'docente_id']);
            $table->index(['inscricao_id', 'avaliado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricao_avaliacoes');
    }
};
