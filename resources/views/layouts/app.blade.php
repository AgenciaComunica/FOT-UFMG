<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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
                        <div>
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
    </body>
</html>
