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

        DB::statement('ALTER TABLE edital_documento_requeridos MODIFY formato_aceito VARCHAR(60) NOT NULL DEFAULT "pdf"');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE edital_documento_requeridos MODIFY formato_aceito VARCHAR(10) NOT NULL DEFAULT "pdf"');
    }
};
