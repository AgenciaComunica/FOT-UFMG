<?php

use App\Http\Controllers\Admin\EditalController as AdminEditalController;
use App\Http\Controllers\Admin\InscricaoController as AdminInscricaoController;
use App\Http\Controllers\Admin\DocenteController as AdminDocenteController;
use App\Http\Controllers\Aluno\PainelController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\Docente\PainelController as DocentePainelController;
use App\Http\Controllers\PublicPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInscricaoController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicPortalController::class, 'index'])->name('home');
Route::post('/inscricoes/verificar', [PublicPortalController::class, 'verificarInscricao'])
    ->middleware('throttle:8,1')
    ->name('public.inscricoes.verificar');
Route::post('/inscricoes/{inscricao}/enviar-informacoes', [PublicPortalController::class, 'enviarInformacoesCompletas'])
    ->middleware('throttle:4,1')
    ->name('public.inscricoes.enviar-informacoes');
Route::post('/inscricoes/{inscricao}/enviar-link-edicao', [PublicPortalController::class, 'enviarLinkEdicao'])
    ->middleware('throttle:4,1')
    ->name('public.inscricoes.enviar-link-edicao');

Route::get('/editais/{edital}/inscricao', [PublicInscricaoController::class, 'create'])->name('public.inscricao.create');
Route::get('/editais/{edital}/arquivo', [PublicPortalController::class, 'downloadEdital'])->name('public.editais.download');
Route::post('/editais/{edital}/inscricao', [PublicInscricaoController::class, 'store'])
    ->middleware('throttle:10,60')
    ->name('public.inscricao.store');
Route::get('/editais/{edital}/inscricao/confirmacao/{protocolo}', [PublicInscricaoController::class, 'confirmacao'])
    ->name('public.inscricao.confirmacao');
Route::get('/inscricoes/{inscricao}/aviso-verificacao', [PublicInscricaoController::class, 'avisoVerificacao'])
    ->name('public.inscricao.email.aviso');
Route::get('/inscricoes/{inscricao}/verificar-email/{token}', [PublicInscricaoController::class, 'verificarEmail'])
    ->name('public.inscricao.email.verificar');
Route::post('/inscricoes/{inscricao}/reenviar-verificacao', [PublicInscricaoController::class, 'reenviarVerificacao'])
    ->middleware('throttle:4,1')
    ->name('public.inscricao.email.reenviar');
Route::get('/inscricoes/{inscricao}/editar/{token}', [PublicInscricaoController::class, 'editWithToken'])
    ->name('public.inscricoes.editar');
Route::put('/inscricoes/{inscricao}/editar/{token}', [PublicInscricaoController::class, 'updateWithToken'])
    ->name('public.inscricoes.editar.update');

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
        Route::get('/painel', [AdminEditalController::class, 'index'])->name('painel');
        Route::get('/inscricoes', [AdminInscricaoController::class, 'index'])->name('inscricoes.index');
        Route::get('/inscricoes/exportar.xls', [AdminInscricaoController::class, 'exportXls'])->name('inscricoes.export');
        Route::resource('editais', AdminEditalController::class, ['parameters' => ['editais' => 'edital']])->except(['show']);
        Route::resource('docentes', AdminDocenteController::class, ['parameters' => ['docentes' => 'docente']])->except(['show']);
        Route::post('docentes/importar', [AdminDocenteController::class, 'import'])->name('docentes.import');
        Route::get('docentes/modelo-importacao', [AdminDocenteController::class, 'downloadTemplate'])->name('docentes.template');
        Route::post('docentes/{docente}/status', [AdminDocenteController::class, 'updateStatus'])->name('docentes.status');
        Route::post('editais/{edital}/publicacao', [AdminEditalController::class, 'updatePublicacao'])->name('editais.publicacao');

        Route::get('/editais/{edital}/inscricoes', [AdminInscricaoController::class, 'byEdital'])->name('editais.inscricoes.index');
        Route::get('/inscricoes/{inscricao}', [AdminInscricaoController::class, 'show'])->name('inscricoes.show');
        Route::put('/inscricoes/{inscricao}', [AdminInscricaoController::class, 'update'])->name('inscricoes.update');
        Route::delete('/inscricoes/{inscricao}', [AdminInscricaoController::class, 'destroy'])->name('inscricoes.destroy');
        Route::post('/inscricoes/{inscricao}/status', [AdminInscricaoController::class, 'updateStatus'])->name('inscricoes.status');
        Route::post('/inscricoes/status/lote', [AdminInscricaoController::class, 'bulkUpdateStatus'])->name('inscricoes.status.bulk');
        Route::post('/inscricoes/excluir/lote', [AdminInscricaoController::class, 'bulkDestroy'])->name('inscricoes.destroy.bulk');
        Route::put('/inscricoes/{inscricao}/documentos/{doc}', [AdminInscricaoController::class, 'updateDocumento'])->name('inscricoes.documentos.update');
        Route::delete('/inscricoes/{inscricao}/documentos/{doc}', [AdminInscricaoController::class, 'destroyDocumento'])->name('inscricoes.documentos.destroy');
        Route::post('/inscricoes/{inscricao}/homologar', [AdminInscricaoController::class, 'homologar'])->name('inscricoes.homologar');
        Route::post('/inscricoes/{inscricao}/indeferir', [AdminInscricaoController::class, 'indeferir'])->name('inscricoes.indeferir');
        Route::post('/inscricoes/{inscricao}/avaliacoes/salvar', [AdminInscricaoController::class, 'salvarAvaliacao'])->name('inscricoes.avaliacoes.salvar');
        Route::post('/inscricoes/{inscricao}/avaliacoes/limpar', [AdminInscricaoController::class, 'limparAvaliacao'])->name('inscricoes.avaliacoes.limpar');
        Route::post('/inscricoes/{inscricao}/avaliacoes/{docente}/lembrete', [AdminInscricaoController::class, 'enviarLembreteAvaliacao'])->name('inscricoes.avaliacoes.lembrete');
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

Route::prefix('docente')
    ->middleware(['auth', 'role:'.User::ROLE_DOCENTE])
    ->name('docente.')
    ->group(function () {
        Route::get('/painel', [DocentePainelController::class, 'index'])->name('painel');
        Route::get('/inscricoes', [DocentePainelController::class, 'index'])->name('inscricoes.index');
        Route::get('/inscricoes/{inscricao}/avaliar', [DocentePainelController::class, 'show'])->name('inscricoes.show');
        Route::post('/inscricoes/{inscricao}/avaliar', [DocentePainelController::class, 'salvarAvaliacao'])->name('inscricoes.salvar');
        Route::post('/inscricoes/{inscricao}/status', [DocentePainelController::class, 'definirVereditoFinal'])->name('inscricoes.status');
        Route::post('/inscricoes/status/lote', [DocentePainelController::class, 'definirVereditoFinalLote'])->name('inscricoes.status.bulk');
        Route::get('/inscricoes/{inscricao}/documentos/{doc}/download', [DocentePainelController::class, 'downloadDocumento'])->name('inscricoes.documentos.download');
    });

require __DIR__.'/auth.php';
