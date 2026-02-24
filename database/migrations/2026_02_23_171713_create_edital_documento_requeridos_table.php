<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edital_documento_requeridos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edital_id')->constrained('editais')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('descricao')->nullable();
            $table->boolean('obrigatorio')->default(true);
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['edital_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edital_documento_requeridos');
    }
};
