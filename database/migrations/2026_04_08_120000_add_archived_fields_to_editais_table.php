<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            if (! Schema::hasColumn('editais', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('arquivo_size');
            }

            if (! Schema::hasColumn('editais', 'archived_by')) {
                $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            if (Schema::hasColumn('editais', 'archived_by')) {
                $table->dropConstrainedForeignId('archived_by');
            }

            if (Schema::hasColumn('editais', 'archived_at')) {
                $table->dropColumn('archived_at');
            }
        });
    }
};
