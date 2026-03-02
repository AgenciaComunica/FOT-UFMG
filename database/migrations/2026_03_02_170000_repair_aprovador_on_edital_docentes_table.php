<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('edital_docentes')) {
            return;
        }

        if (Schema::hasColumn('edital_docentes', 'aprovador')) {
            return;
        }

        Schema::table('edital_docentes', function (Blueprint $table) {
            $table->boolean('aprovador')->default(false)->after('ordem');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('edital_docentes')) {
            return;
        }

        if (! Schema::hasColumn('edital_docentes', 'aprovador')) {
            return;
        }

        Schema::table('edital_docentes', function (Blueprint $table) {
            $table->dropColumn('aprovador');
        });
    }
};

