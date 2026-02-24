<x-guest-layout>
    <div class="mx-auto w-full max-w-[450px] rounded-3xl border border-white/70 bg-white/85 p-6 shadow-2xl backdrop-blur-xl">
        <div class="mb-4 border-b border-slate-200 pb-4">
            <div class="flex flex-col gap-3">
                <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="w-[400px] h-auto">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Área interna</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Entrar na plataforma</h1>
                    <p class="mt-1 text-sm text-slate-600">Acesso exclusivo para secretaria e alunos aprovados.</p>
                </div>
            </div>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-3">
            @csrf

            <div>
                <x-input-label for="email" :value="__('E-mail')" />
                <x-text-input id="email" class="mt-1 block w-full rounded-xl border-slate-300 bg-white/80 px-4 py-2.5" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Senha')" />
                <x-text-input id="password" class="mt-1 block w-full rounded-xl border-slate-300 bg-white/80 px-4 py-2.5" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="block pt-1">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Lembrar de mim') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-between pt-1">
                @if (Route::has('password.request'))
                    <a class="text-sm text-slate-600 underline hover:text-slate-900" href="{{ route('password.request') }}">
                        {{ __('Esqueceu sua senha?') }}
                    </a>
                @endif

                <x-primary-button class="rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold tracking-wide text-white hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-900">
                    {{ __('Entrar') }}
                </x-primary-button>
            </div>
        </form>

        <p class="mt-5 text-center text-xs text-slate-500">
            Não tem login ainda?
            <a href="{{ route('home') }}" class="font-semibold text-sky-700 hover:text-sky-600">
                Faça sua inscrição
            </a>
        </p>
    </div>
</x-guest-layout>
