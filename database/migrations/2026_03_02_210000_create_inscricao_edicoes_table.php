<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscricao_edicoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscricao_id')->constrained('inscricoes')->cascadeOnDelete();
            $table->text('motivo');
            $table->dateTime('edited_at');
            $table->timestamps();

            $table->index(['inscricao_id', 'edited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscricao_edicoes');
    }
};

