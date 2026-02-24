<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->foreignId('edital_id')->nullable()->after('id')->constrained('editais')->cascadeOnDelete();
            $table->text('indeferimento_motivo')->nullable()->after('decided_by');
        });

        DB::table('inscricoes')
            ->where('status', 'APROVADA')
            ->update(['status' => 'HOMOLOGADA']);

        DB::table('inscricoes')
            ->where('status', 'REJEITADA')
            ->update(['status' => 'INDEFERIDA']);

        if (Schema::hasColumn('inscricoes', 'rejection_reason')) {
            DB::table('inscricoes')->whereNull('indeferimento_motivo')->update([
                'indeferimento_motivo' => DB::raw('rejection_reason'),
            ]);

            Schema::table('inscricoes', function (Blueprint $table) {
                $table->dropColumn('rejection_reason');
            });
        }
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        DB::table('inscricoes')->where('status', 'HOMOLOGADA')->update(['status' => 'APROVADA']);
        DB::table('inscricoes')->where('status', 'INDEFERIDA')->update(['status' => 'REJEITADA']);
        DB::table('inscricoes')->whereNull('rejection_reason')->update([
            'rejection_reason' => DB::raw('indeferimento_motivo'),
        ]);

        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edital_id');
            $table->dropColumn('indeferimento_motivo');
        });
    }
};
