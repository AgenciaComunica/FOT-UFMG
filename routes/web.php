<?php

use App\Http\Controllers\Aluno\PainelController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInscricaoController;
use App\Http\Controllers\Secretaria\InscricaoController as SecretariaInscricaoController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/inscricao', [PublicInscricaoController::class, 'create'])->name('inscricao.create');
Route::post('/inscricao', [PublicInscricaoController::class, 'store'])
    ->middleware('throttle:10,60')
    ->name('inscricao.store');
Route::get('/inscricao/confirmacao/{protocolo}', [PublicInscricaoController::class, 'confirmacao'])
    ->name('inscricao.confirmacao');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('secretaria')
    ->middleware(['auth', 'role:'.User::ROLE_SECRETARIA])
    ->name('secretaria.')
    ->group(function () {
        Route::get('/inscricoes', [SecretariaInscricaoController::class, 'index'])->name('inscricoes.index');
        Route::get('/inscricoes/{inscricao}', [SecretariaInscricaoController::class, 'show'])->name('inscricoes.show');
        Route::post('/inscricoes/{inscricao}/aprovar', [SecretariaInscricaoController::class, 'aprovar'])->name('inscricoes.aprovar');
        Route::post('/inscricoes/{inscricao}/rejeitar', [SecretariaInscricaoController::class, 'rejeitar'])->name('inscricoes.rejeitar');
        Route::get('/inscricoes/{inscricao}/documentos/{doc}/download', [SecretariaInscricaoController::class, 'downloadDocumento'])
            ->name('inscricoes.documentos.download');
    });

Route::prefix('aluno')
    ->middleware(['auth', 'role:'.User::ROLE_ALUNO])
    ->name('aluno.')
    ->group(function () {
        Route::get('/painel', [PainelController::class, 'index'])->name('painel');
        Route::get('/documentos/{doc}/download', [PainelController::class, 'downloadDocumento'])->name('documentos.download');
    });

require __DIR__.'/auth.php';
