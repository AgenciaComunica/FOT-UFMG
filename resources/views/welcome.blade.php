<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sistema de Secretaria - Fisioterapia UFMG</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased bg-gray-100 text-gray-900">
        <main class="min-h-screen flex items-center justify-center px-4">
            <section class="max-w-2xl w-full bg-white shadow rounded-lg p-8 space-y-6">
                <h1 class="text-2xl font-bold">Sistema de Secretaria - Fisioterapia (Ortopedia e Trauma)</h1>
                <p class="text-sm text-gray-600">Inscricao publica para candidatos e painel interno para secretaria/alunos aprovados.</p>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('inscricao.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-500">Fazer inscricao</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 hover:bg-gray-50">Login interno</a>
                </div>
            </section>
        </main>
    </body>
</html>
