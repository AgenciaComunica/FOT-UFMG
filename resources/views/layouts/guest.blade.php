<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/png" href="{{ asset('images/Icone-FTO.png') }}">

        <title>{{ config('app.name', 'FOT-UFMG') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased text-slate-900" style="font-family: Manrope, sans-serif;">
        <div class="relative min-h-screen overflow-hidden bg-[linear-gradient(120deg,#0d3f77_0%,#1d5ca0_42%,#3db7b4_74%,#7ce4c2_100%)]">
            <div class="pointer-events-none absolute -top-32 -left-20 h-72 w-72 rounded-[42%] bg-[#2565aa]/40 blur-2xl anim-float-slow"></div>
            <div class="pointer-events-none absolute -top-8 right-[-70px] h-64 w-64 rounded-[36%] bg-[#a2c939]/25 blur-2xl anim-float-reverse"></div>
            <div class="pointer-events-none absolute bottom-[-90px] left-[25%] h-80 w-80 rounded-[45%] bg-[#a2c939]/20 blur-3xl anim-float-slow"></div>
            <div class="pointer-events-none absolute bottom-[-120px] right-[-80px] h-96 w-96 rounded-[44%] bg-[#2565aa]/30 blur-3xl anim-float-reverse"></div>
            <div class="pointer-events-none absolute left-0 right-0 top-[46%] h-40 anim-drift opacity-45" style="background-image: radial-gradient(140% 80% at 50% 50%, #ffffff66 0, #ffffff00 65%);"></div>
            <div class="pointer-events-none absolute left-[-8%] right-[-8%] top-[52%] h-40 anim-drift opacity-35" style="background-image: repeating-radial-gradient(ellipse at center, transparent 0 7px, #ffffff26 8px 9px);"></div>
            <div class="pointer-events-none absolute inset-0 opacity-20 mix-blend-screen" style="background-image: url('{{ asset('images/Fundo-site.svg') }}'); background-size: cover; background-position: center;"></div>
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,#ffffff33_0%,transparent_35%),radial-gradient(circle_at_78%_78%,#ffffff2b_0%,transparent_32%)]"></div>

            <div class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center justify-center px-4 py-8 lg:px-8">
                <div class="w-full max-w-4xl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
