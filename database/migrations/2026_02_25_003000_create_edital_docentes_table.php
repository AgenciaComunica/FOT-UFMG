<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('edital_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edital_id')->constrained('editais')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->timestamps();

            $table->unique(['edital_id', 'user_id']);
            $table->index(['edital_id', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('edital_docentes');
    }
};
