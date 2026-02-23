<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Secretaria FOT-UFMG</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-10">
            <section class="w-full rounded-2xl bg-white p-8 shadow-xl ring-1 ring-slate-200 md:p-12">
                <p class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-800">
                    Fisioterapia UFMG
                </p>

                <h1 class="mt-4 text-3xl font-bold leading-tight text-slate-900 md:text-4xl">
                    Sistema da Secretaria<br>Ortopedia e Trauma
                </h1>

                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 md:text-base">
                    Inicie sua inscrição no edital aberto ou acesse o login da plataforma.
                </p>

                @if ($editalAberto)
                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                        Inscrições abertas para <strong>{{ $editalAberto->titulo }}</strong> até {{ $editalAberto->periodo_inscricao_fim->format('d/m/Y H:i') }}.
                    </div>
                @else
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        Não há edital aberto no momento.
                    </div>
                @endif

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    @if ($editalAberto)
                        <a href="{{ route('public.inscricao.create', $editalAberto) }}" class="group rounded-xl border border-sky-200 bg-sky-50 p-5 transition hover:border-sky-300 hover:bg-sky-100">
                            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Candidato</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Nova inscrição</h2>
                            <p class="mt-2 text-sm text-slate-600">Preencha seus dados e envie os documentos em PDF.</p>
                            <span class="mt-4 inline-flex text-sm font-semibold text-sky-800">Começar inscrição</span>
                        </a>
                    @else
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 opacity-70">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Candidato</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Inscrições fechadas</h2>
                            <p class="mt-2 text-sm text-slate-600">Aguarde a abertura do próximo edital.</p>
                        </div>
                    @endif

                    <a href="{{ route('login') }}" class="group rounded-xl border border-slate-300 bg-white p-5 transition hover:border-slate-400 hover:bg-slate-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Admin e alunos homologados</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Fazer login</h2>
                        <p class="mt-2 text-sm text-slate-600">Acompanhe inscrições, homologações e documentos.</p>
                        <span class="mt-4 inline-flex text-sm font-semibold text-slate-800">Acessar plataforma</span>
                    </a>
                </div>

                <p class="mt-8 text-xs text-slate-500">Não tem login ainda? Faça sua inscrição no edital aberto.</p>
            </section>
        </main>
    </body>
</html>
