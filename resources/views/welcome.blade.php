<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{ asset('images/Icone-FTO.png') }}">
        <title>Secretaria FOT-UFMG</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(120deg,#0d3f77_0%,#1d5ca0_42%,#3db7b4_74%,#7ce4c2_100%)]">
            <div class="pointer-events-none absolute -top-32 -left-20 h-72 w-72 rounded-[42%] bg-[#2565aa]/40 blur-2xl anim-float-slow"></div>
            <div class="pointer-events-none absolute -top-8 right-[-70px] h-64 w-64 rounded-[36%] bg-[#a2c939]/25 blur-2xl anim-float-reverse"></div>
            <div class="pointer-events-none absolute bottom-[-90px] left-[25%] h-80 w-80 rounded-[45%] bg-[#a2c939]/20 blur-3xl anim-float-slow"></div>
            <div class="pointer-events-none absolute bottom-[-120px] right-[-80px] h-96 w-96 rounded-[44%] bg-[#2565aa]/30 blur-3xl anim-float-reverse"></div>
            <div class="pointer-events-none absolute left-0 right-0 top-[46%] h-40 anim-drift opacity-45" style="background-image: radial-gradient(140% 80% at 50% 50%, #ffffff66 0, #ffffff00 65%);"></div>
            <div class="pointer-events-none absolute left-[-8%] right-[-8%] top-[52%] h-40 anim-drift opacity-35" style="background-image: repeating-radial-gradient(ellipse at center, transparent 0 7px, #ffffff26 8px 9px);"></div>
            <div class="pointer-events-none absolute inset-0 opacity-20 mix-blend-screen" style="background-image: url('{{ asset('images/Fundo-site.svg') }}'); background-size: cover; background-position: center;"></div>
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,#ffffff33_0%,transparent_35%),radial-gradient(circle_at_78%_78%,#ffffff2b_0%,transparent_32%)]"></div>

        <main class="relative mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-10">
            <section class="w-full rounded-2xl bg-white/70 p-8 shadow-2xl backdrop-blur-md md:p-12">
                <div class="mb-6 flex justify-center">
                    <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="w-[400px] h-auto">
                </div>

                <div class="mt-8 grid gap-4 md:grid-cols-2">
                    @if ($editalAberto)
                        <div class="group relative rounded-xl border border-[#a2c939] bg-[#a2c939] p-5 shadow-[0_12px_28px_-14px_rgba(162,201,57,0.70)] transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_18px_38px_-16px_rgba(162,201,57,0.78)]">
                            <span class="absolute right-4 top-4 inline-flex rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-white">
                                Inscrição Aberta até {{ $editalAberto->periodo_inscricao_fim->format('d/m/Y H:i') }}
                            </span>
                            <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/30 text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H4zm2 4a1 1 0 000 2h8a1 1 0 100-2H6zm0 4a1 1 0 000 2h5a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-white/90">Candidato</p>
                            <h2 class="mt-2 text-xl font-semibold text-white">Nova inscrição</h2>
                            <p class="mt-2 text-sm text-white/90">Preencha seus dados e envie os documentos em PDF.</p>
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                                <a href="{{ route('public.inscricao.create', $editalAberto) }}" class="inline-flex items-center rounded-md border border-white bg-transparent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/10">
                                    Inscrever
                                </a>
                                <a href="{{ route('public.inscricao.create', $editalAberto) }}" class="inline-flex items-center rounded-md border border-white bg-transparent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/10">
                                    Ver Edital
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="group rounded-xl border border-slate-300 bg-slate-50 p-5 shadow-[0_12px_28px_-14px_rgba(100,116,139,0.45)] opacity-70 transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_18px_38px_-16px_rgba(100,116,139,0.55)]">
                            <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-200 text-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V7.414A2 2 0 0017.414 6L14 2.586A2 2 0 0012.586 2H4zm2 4a1 1 0 000 2h8a1 1 0 100-2H6zm0 4a1 1 0 000 2h5a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Candidato</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Inscrições fechadas</h2>
                            <p class="mt-2 text-sm text-slate-600">Aguarde a abertura do próximo edital.</p>
                        </div>
                    @endif

                    <div class="group rounded-xl border border-[#2565aa] bg-[#2565aa] p-5 shadow-[0_12px_28px_-14px_rgba(37,101,170,0.70)] transition-all duration-300 hover:scale-[1.02] hover:shadow-[0_18px_38px_-16px_rgba(37,101,170,0.78)]">
                        <div class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/30 text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v2H5a2 2 0 00-2 2v5a2 2 0 002 2h10a2 2 0 002-2v-5a2 2 0 00-2-2h-1V6a4 4 0 00-4-4zm-2 6V6a2 2 0 114 0v2H8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-white/90">Login</p>
                        <h2 class="mt-2 text-xl font-semibold text-white">Acessar a Plataforma</h2>
                        <p class="mt-2 text-sm text-white/90">Acompanhe inscrições, homologações e documentos.</p>
                        <a href="{{ route('login') }}" class="mt-4 inline-flex self-center items-center rounded-md border border-white bg-transparent px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/10">
                            Acessar
                        </a>
                    </div>
                </div>
            </section>
        </main>
        </div>
    </body>
</html>
