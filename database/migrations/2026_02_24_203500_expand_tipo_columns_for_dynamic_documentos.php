<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE edital_documento_requeridos MODIFY tipo VARCHAR(120) NOT NULL');
        DB::statement('ALTER TABLE inscricao_documentos MODIFY tipo VARCHAR(120) NOT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE edital_documento_requeridos MODIFY tipo VARCHAR(40) NOT NULL');
        DB::statement('ALTER TABLE inscricao_documentos MODIFY tipo VARCHAR(40) NOT NULL');
    }
};
