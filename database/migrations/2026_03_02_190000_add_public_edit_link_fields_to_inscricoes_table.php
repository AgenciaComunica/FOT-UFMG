<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table): void {
            if (! Schema::hasColumn('inscricoes', 'edit_link_token')) {
                $table->string('edit_link_token', 64)->nullable()->after('resultado_email_sent_at');
            }
            if (! Schema::hasColumn('inscricoes', 'edit_link_sent_at')) {
                $table->dateTime('edit_link_sent_at')->nullable()->after('edit_link_token');
            }
            if (! Schema::hasColumn('inscricoes', 'edit_link_expires_at')) {
                $table->dateTime('edit_link_expires_at')->nullable()->after('edit_link_sent_at');
            }
            if (! Schema::hasColumn('inscricoes', 'edit_link_used_at')) {
                $table->dateTime('edit_link_used_at')->nullable()->after('edit_link_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table): void {
            $columns = [
                'edit_link_used_at',
                'edit_link_expires_at',
                'edit_link_sent_at',
                'edit_link_token',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('inscricoes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

