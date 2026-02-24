<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->string('email_verification_token', 64)->nullable()->after('telefone');
            $table->dateTime('verification_sent_at')->nullable()->after('email_verification_token');
            $table->dateTime('email_verified_at')->nullable()->after('verification_sent_at');
            $table->dateTime('resultado_email_sent_at')->nullable()->after('email_verified_at');

            $table->index('email_verification_token');
            $table->index('email_verified_at');
            $table->index('resultado_email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('inscricoes', function (Blueprint $table) {
            $table->dropIndex(['email_verification_token']);
            $table->dropIndex(['email_verified_at']);
            $table->dropIndex(['resultado_email_sent_at']);

            $table->dropColumn([
                'email_verification_token',
                'verification_sent_at',
                'email_verified_at',
                'resultado_email_sent_at',
            ]);
        });
    }
};

