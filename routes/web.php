<?php

use App\Http\Controllers\Admin\EditalController as AdminEditalController;
use App\Http\Controllers\Admin\InscricaoController as AdminInscricaoController;
use App\Http\Controllers\Aluno\PainelController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInscricaoController;
use App\Models\Edital;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $editalAberto = Edital::query()
        ->where('publicado', true)
        ->where('periodo_inscricao_inicio', '<=', now())
        ->where('periodo_inscricao_fim', '>=', now())
        ->orderByDesc('periodo_inscricao_inicio')
        ->first();

    return view('welcome', [
        'editalAberto' => $editalAberto,
    ]);
})->name('home');

Route::get('/editais/{edital}/inscricao', [PublicInscricaoController::class, 'create'])->name('public.inscricao.create');
Route::get('/editais/{edital}/arquivo', [AdminEditalController::class, 'downloadArquivo'])->name('public.editais.download');
Route::post('/editais/{edital}/inscricao', [PublicInscricaoController::class, 'store'])
    ->middleware('throttle:10,60')
    ->name('public.inscricao.store');
Route::get('/editais/{edital}/inscricao/confirmacao/{protocolo}', [PublicInscricaoController::class, 'confirmacao'])
    ->name('public.inscricao.confirmacao');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')
    ->middleware(['auth', 'role:'.User::ROLE_ADMIN])
    ->name('admin.')
    ->group(function () {
        Route::resource('editais', AdminEditalController::class, ['parameters' => ['editais' => 'edital']])->except(['show']);
        Route::post('editais/{edital}/publicacao', [AdminEditalController::class, 'updatePublicacao'])->name('editais.publicacao');

        Route::get('/editais/{edital}/inscricoes', [AdminInscricaoController::class, 'index'])->name('editais.inscricoes.index');
        Route::get('/inscricoes/{inscricao}', [AdminInscricaoController::class, 'show'])->name('inscricoes.show');
        Route::post('/inscricoes/{inscricao}/homologar', [AdminInscricaoController::class, 'homologar'])->name('inscricoes.homologar');
        Route::post('/inscricoes/{inscricao}/indeferir', [AdminInscricaoController::class, 'indeferir'])->name('inscricoes.indeferir');
        Route::get('/inscricoes/{inscricao}/documentos/{doc}/download', [AdminInscricaoController::class, 'downloadDocumento'])
            ->name('inscricoes.documentos.download');

        Route::get('/editais/{edital}/relatorios/inscricoes-recebidas.csv', [AdminInscricaoController::class, 'relatorioInscricoesRecebidasCsv'])
            ->name('editais.relatorios.inscricoes-recebidas');
        Route::get('/editais/{edital}/relatorios/inscricoes-homologadas.csv', [AdminInscricaoController::class, 'relatorioInscricoesHomologadasCsv'])
            ->name('editais.relatorios.inscricoes-homologadas');
    });

Route::prefix('aluno')
    ->middleware(['auth', 'role:'.User::ROLE_ALUNO])
    ->name('aluno.')
    ->group(function () {
        Route::get('/painel', [PainelController::class, 'index'])->name('painel');
        Route::get('/inscricoes', [PainelController::class, 'inscricoes'])->name('inscricoes.index');
        Route::get('/inscricoes/{inscricao}', [PainelController::class, 'show'])->name('inscricoes.show');
        Route::get('/documentos/{doc}/download', [PainelController::class, 'downloadDocumento'])->name('documentos.download');
    });

require __DIR__.'/auth.php';
