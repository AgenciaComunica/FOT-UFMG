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
                    Escolha abaixo o que você deseja fazer: iniciar uma nova inscrição pública ou acessar a plataforma interna.
                </p>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    <a href="{{ route('inscricao.create') }}" class="group rounded-xl border border-sky-200 bg-sky-50 p-5 transition hover:border-sky-300 hover:bg-sky-100">
                        <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Candidato</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Nova inscrição</h2>
                        <p class="mt-2 text-sm text-slate-600">Preencha seus dados e envie os documentos em PDF.</p>
                        <span class="mt-4 inline-flex text-sm font-semibold text-sky-800">Começar inscrição</span>
                    </a>

                    <a href="{{ route('login') }}" class="group rounded-xl border border-slate-300 bg-white p-5 transition hover:border-slate-400 hover:bg-slate-50">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Secretaria e alunos aprovados</p>
                        <h2 class="mt-2 text-xl font-semibold text-slate-900">Fazer login</h2>
                        <p class="mt-2 text-sm text-slate-600">Acompanhe inscrições, aprovações e documentos.</p>
                        <span class="mt-4 inline-flex text-sm font-semibold text-slate-800">Acessar plataforma</span>
                    </a>
                </div>
            </section>
        </main>
    </body>
</html>
