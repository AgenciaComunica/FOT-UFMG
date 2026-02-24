<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('edital_docentes', function (Blueprint $table) {
            $table->boolean('aprovador')->default(false)->after('ordem');
        });
    }

    public function down(): void
    {
        Schema::table('edital_docentes', function (Blueprint $table) {
            $table->dropColumn('aprovador');
        });
    }
};

