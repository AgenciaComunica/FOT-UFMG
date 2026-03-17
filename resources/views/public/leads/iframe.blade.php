<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Newsletter FOT-UFMG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased text-slate-900" style="font-family: Manrope, sans-serif;" x-data="newsletterFrame()" x-init="init()">
    <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(120deg,#0d3f77_0%,#1d5ca0_42%,#3db7b4_74%,#7ce4c2_100%)]">
        <div class="pointer-events-none absolute -top-24 -left-16 h-56 w-56 rounded-[42%] bg-[#2565aa]/40 blur-2xl anim-float-slow"></div>
        <div class="pointer-events-none absolute -top-6 right-[-60px] h-52 w-52 rounded-[36%] bg-[#a2c939]/25 blur-2xl anim-float-reverse"></div>
        <div class="pointer-events-none absolute bottom-[-80px] left-[22%] h-64 w-64 rounded-[45%] bg-[#a2c939]/20 blur-3xl anim-float-slow"></div>
        <div class="pointer-events-none absolute bottom-[-90px] right-[-60px] h-72 w-72 rounded-[44%] bg-[#2565aa]/30 blur-3xl anim-float-reverse"></div>
        <div class="pointer-events-none absolute inset-0 opacity-20 mix-blend-screen" style="background-image: url('{{ asset('images/Fundo-site.svg') }}'); background-size: cover; background-position: center;"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,#ffffff33_0%,transparent_35%),radial-gradient(circle_at_78%_78%,#ffffff2b_0%,transparent_32%)]"></div>

        <div class="relative flex min-h-screen items-center justify-center p-5">
            <div class="mx-auto w-full max-w-xl rounded-3xl border border-white/70 bg-white/85 p-6 shadow-2xl backdrop-blur-xl">
            <div class="mb-5 text-center">
                <img
                    src="{{ asset('images/Logo-FTO.png') }}"
                    alt="Logo FOT-UFMG"
                    class="mx-auto mb-5 h-20 w-auto"
                >
                <h1 class="text-lg font-bold text-slate-900">Receba novidades dos editais</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Cadastre-se para receber avisos sobre abertura e encerramento de editais.
                </p>
            </div>

            @if ($statusMessage)
                <div class="py-6 text-center">
                    <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-7.25 7.25a1 1 0 01-1.414 0l-3.25-3.25a1 1 0 011.414-1.414l2.543 2.543 6.543-6.543a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-base font-semibold text-slate-900">{{ $statusMessage }}</p>
                    <button type="button" class="btn-primary mt-6 w-full" @click="closeNow()">
                        Sair <span x-text="countdown > 0 ? `(${countdown}s)` : ''"></span>
                    </button>
                </div>
            @else
                @if ($formErrors->any())
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                        <ul class="list-disc pl-5">
                            @foreach ($formErrors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('public.leads.store') }}" class="space-y-4">
                    @csrf
                    <input type="text" name="{{ $honeypotField }}" class="hidden" tabindex="-1" autocomplete="off">
                    <div>
                        <label for="nome" class="mb-1 block text-sm font-semibold text-slate-700">Nome</label>
                        <input id="nome" name="nome" type="text" class="input-base !mt-0" value="{{ $oldInput['nome'] ?? '' }}" required>
                    </div>
                    <div>
                        <label for="email" class="mb-1 block text-sm font-semibold text-slate-700">E-mail</label>
                        <input id="email" name="email" type="email" class="input-base !mt-0" value="{{ $oldInput['email'] ?? '' }}" required>
                    </div>
                    <button type="submit" class="btn-primary w-full">Quero receber novidades</button>
                </form>
            @endif
            </div>
        </div>
    </div>

    <script>
        function newsletterFrame() {
            return {
                countdown: 5,
                timerId: null,
                hasSuccess: {{ $statusMessage ? 'true' : 'false' }},
                init() {
                    if (!this.hasSuccess) {
                        return;
                    }

                    this.timerId = window.setInterval(() => {
                        this.countdown -= 1;

                        if (this.countdown <= 0) {
                            this.closeNow();
                        }
                    }, 1000);
                },
                closeNow() {
                    if (this.timerId) {
                        window.clearInterval(this.timerId);
                        this.timerId = null;
                    }

                    if (window.parent && window.parent !== window) {
                        window.parent.postMessage({ type: 'newsletter-close' }, '*');
                    }
                },
            };
        }
    </script>
</body>
</html>
