<x-guest-layout>
    <div x-data="{ sentModalOpen: {{ session('status') ? 'true' : 'false' }} }" x-init="if (sentModalOpen) { setTimeout(() => window.location.href='{{ route('login') }}', 2000) }">
        <div class="mx-auto w-full max-w-[450px] rounded-3xl border border-white/70 bg-white/85 p-6 shadow-2xl backdrop-blur-xl">
            <div class="mb-4 border-b border-slate-200 pb-4">
                <div class="flex flex-col gap-3">
                    <div class="flex justify-end">
                        <a href="{{ route('login') }}" class="btn-muted">Voltar para login</a>
                    </div>
                    <img src="{{ asset('images/Logo-FTO.png') }}" alt="Logo FOT-UFMG" class="w-[320px] h-auto">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Redefinição de senha</p>
                        <h1 class="mt-1 text-2xl font-extrabold text-slate-900">Definir nova senha</h1>
                        <p class="mt-1 text-sm text-slate-600">Crie uma nova senha para continuar acessando a plataforma.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <input type="hidden" name="email" value="{{ old('email', $request->email) }}">

                <div>
                    <x-input-label for="password" :value="__('Nova senha')" />
                    <x-text-input id="password" class="mt-1 block w-full rounded-xl border-slate-300 bg-white/80 px-4 py-2.5" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar nova senha')" />
                    <x-text-input id="password_confirmation" class="mt-1 block w-full rounded-xl border-slate-300 bg-white/80 px-4 py-2.5" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end">
                    <x-primary-button class="rounded-xl bg-slate-900 px-6 py-3 text-sm font-semibold tracking-wide text-white hover:bg-slate-800 focus:bg-slate-800 active:bg-slate-900">
                        Redefinir senha
                    </x-primary-button>
                </div>
            </form>
        </div>

        <div
            x-show="sentModalOpen"
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            style="display:none;"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-lg">
                <h3 class="text-lg font-bold text-slate-900">Senha redefinida</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Sua senha foi redefinida com sucesso.
                    Você será redirecionado para o login.
                </p>
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('login') }}" class="btn-primary">Ir para login</a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
