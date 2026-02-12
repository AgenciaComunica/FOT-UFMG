<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FOT-UFMG') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased text-slate-900" style="font-family: Manrope, sans-serif;">
        <div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_5%_10%,#fef3c7_0%,transparent_30%),radial-gradient(circle_at_95%_85%,#bfdbfe_0%,transparent_35%),linear-gradient(135deg,#f8fafc_0%,#eef2ff_45%,#ecfeff_100%)]">
            <div class="absolute -top-24 left-1/4 h-72 w-72 rounded-full bg-amber-300/20 blur-3xl"></div>
            <div class="absolute -bottom-24 right-1/4 h-72 w-72 rounded-full bg-sky-300/20 blur-3xl"></div>

            <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-8 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <section class="mb-8 lg:mb-0 lg:max-w-xl">
                    <a href="/" class="inline-flex items-center gap-3 rounded-full bg-white/70 px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 backdrop-blur">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        UFMG · Fisioterapia
                    </a>
                    <h1 class="mt-6 text-3xl font-bold leading-tight text-slate-900 md:text-5xl" style="font-family: Fraunces, serif;">
                        Secretaria Digital<br>Ortopedia e Trauma
                    </h1>
                    <p class="mt-4 max-w-lg text-sm leading-relaxed text-slate-600 md:text-base">
                        Plataforma de inscrição, análise e acompanhamento do processo acadêmico com acesso seguro para secretaria e alunos aprovados.
                    </p>
                </section>

                <div class="w-full max-w-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
