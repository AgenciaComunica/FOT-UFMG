<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->string('criterio_nota_corte', 25)->default('NUMERO_VAGAS')->after('publicado');
            $table->decimal('nota_corte_fixa', 6, 2)->nullable()->after('criterio_nota_corte');
            $table->decimal('nota_corte_offset', 6, 2)->nullable()->after('nota_corte_fixa');
            $table->unsignedInteger('numero_vagas')->nullable()->after('nota_corte_offset');
            $table->index('criterio_nota_corte');
        });
    }

    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->dropIndex(['criterio_nota_corte']);
            $table->dropColumn([
                'criterio_nota_corte',
                'nota_corte_fixa',
                'nota_corte_offset',
                'numero_vagas',
            ]);
        });
    }
};
