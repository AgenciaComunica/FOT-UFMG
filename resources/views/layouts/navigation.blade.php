<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/Icone-FTO.png') }}" alt="Logo FOT-UFMG" class="h-9 w-9 rounded-lg object-cover">
                <span class="hidden text-sm font-semibold text-slate-800 md:inline">Secretaria Fisioterapia</span>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                    <x-nav-link :href="route('admin.painel')" :active="request()->routeIs('admin.painel') || request()->routeIs('admin.editais.*')">Painel</x-nav-link>
                    <x-nav-link :href="route('admin.inscricoes.index')" :active="request()->routeIs('admin.inscricoes.*') || request()->routeIs('admin.editais.inscricoes.*')">Inscrições</x-nav-link>
                @else
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                @endif

                @if (auth()->user()->role === \App\Models\User::ROLE_ALUNO)
                    <x-nav-link :href="route('aluno.inscricoes.index')" :active="request()->routeIs('aluno.*')">Minhas inscrições</x-nav-link>
                @endif
            </div>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            <div class="text-right">
                <p class="text-sm font-semibold text-slate-800">{{ Auth::user()->name }}</p>
                <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
            </div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="btn-muted !px-3 !py-2">Conta</button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Perfil</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Sair</x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button @click="open = !open" class="btn-muted !px-3 !py-2 md:hidden">Menu</button>
    </div>

    <div x-show="open" x-transition class="border-t border-slate-200 bg-white px-4 py-3 md:hidden">
        <div class="space-y-1">
            @if (auth()->user()->role === \App\Models\User::ROLE_ADMIN)
                <x-responsive-nav-link :href="route('admin.painel')" :active="request()->routeIs('admin.painel') || request()->routeIs('admin.editais.*')">Painel</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.inscricoes.index')" :active="request()->routeIs('admin.inscricoes.*') || request()->routeIs('admin.editais.inscricoes.*')">Inscrições</x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            @endif
            @if (auth()->user()->role === \App\Models\User::ROLE_ALUNO)
                <x-responsive-nav-link :href="route('aluno.inscricoes.index')" :active="request()->routeIs('aluno.*')">Minhas inscrições</x-responsive-nav-link>
            @endif
        </div>

        <div class="mt-4 border-t border-slate-200 pt-3">
            <x-responsive-nav-link :href="route('profile.edit')">Perfil</x-responsive-nav-link>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Sair</x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
