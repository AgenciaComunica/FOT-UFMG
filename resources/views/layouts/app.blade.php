<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/Icone-FTO.png') }}">

        <title>{{ config('app.name', 'FOT-UFMG') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell antialiased" style="font-family: Manrope, sans-serif;">
        <div class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
                    <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                        <div class="min-w-0 flex-1">
                            {{ $header }}
                        </div>
                        @isset($breadcrumb)
                            <p class="hidden text-xs font-medium text-slate-500 md:block">{{ $breadcrumb }}</p>
                        @endisset
                    </div>
                </header>
            @endisset

            <main class="py-8">
                {{ $slot }}
            </main>
        </div>
        <script>
            (() => {
                const key = 'preserve-focus::' + window.location.pathname;

                const saveFocusState = (el) => {
                    if (!el || el.getAttribute('data-preserve-focus') !== '1') return;
                    const payload = {
                        name: el.getAttribute('name') || null,
                        id: el.id || null,
                        start: typeof el.selectionStart === 'number' ? el.selectionStart : null,
                        end: typeof el.selectionEnd === 'number' ? el.selectionEnd : null,
                    };
                    sessionStorage.setItem(key, JSON.stringify(payload));
                };

                document.addEventListener('input', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)) return;
                    saveFocusState(target);
                }, true);

                window.addEventListener('beforeunload', () => {
                    const active = document.activeElement;
                    if (active instanceof HTMLInputElement || active instanceof HTMLTextAreaElement) {
                        saveFocusState(active);
                    }
                });

                window.addEventListener('load', () => {
                    const raw = sessionStorage.getItem(key);
                    if (!raw) return;

                    let state = null;
                    try {
                        state = JSON.parse(raw);
                    } catch (_) {
                        sessionStorage.removeItem(key);
                        return;
                    }

                    if (!state) return;

                    let el = null;
                    if (state.id) {
                        el = document.getElementById(state.id);
                    }
                    if (!el && state.name) {
                        el = document.querySelector(`[data-preserve-focus=\"1\"][name=\"${CSS.escape(state.name)}\"]`);
                    }
                    if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) return;

                    requestAnimationFrame(() => {
                        el.focus();
                        if (typeof state.start === 'number' && typeof state.end === 'number') {
                            el.setSelectionRange(state.start, state.end);
                        }
                    });
                });
            })();
        </script>
    </body>
</html>
