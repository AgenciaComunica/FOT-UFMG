<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->boolean('publicado')->default(true)->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->dropColumn('publicado');
        });
    }
};
