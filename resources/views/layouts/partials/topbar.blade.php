<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 sm:px-6 lg:px-8">
    <button type="button" class="btn-ghost -ml-2 p-2 lg:hidden" @click="sidebar = true" aria-label="Abrir menú">
        <x-heroicon-o-bars-3 class="h-6 w-6" />
    </button>

    <div class="min-w-0 flex-1">
        <p class="truncate text-base font-semibold">{{ $title ?? '' }}</p>
    </div>

    <div class="flex items-center gap-1 sm:gap-2">
        {{-- Tema claro / oscuro / sistema --}}
        <button type="button" x-data="themeToggle(@js(route('preferences.theme')))" @click="cycle()" :title="label" :aria-label="label" class="btn-ghost p-2">
            <x-heroicon-o-sun class="h-5 w-5" x-show="theme === 'light'" />
            <x-heroicon-o-moon class="h-5 w-5" x-show="theme === 'dark'" x-cloak />
            <x-heroicon-o-computer-desktop class="h-5 w-5" x-show="theme === 'system'" x-cloak />
        </button>

        {{-- Notificaciones --}}
        <x-dropdown align="right" width="w-80" content-classes="bg-white dark:bg-slate-800">
            <x-slot name="trigger">
                <button type="button" class="btn-ghost relative p-2" aria-label="Notificaciones">
                    <x-heroicon-o-bell class="h-5 w-5" />
                    @if (($unreadCount ?? 0) > 0)
                        <span class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="flex items-center justify-between px-4 py-2">
                    <p class="text-sm font-semibold">Notificaciones</p>
                    @if (($unreadCount ?? 0) > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button class="text-xs link">Marcar todas como leídas</button>
                        </form>
                    @endif
                </div>
                <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-700">
                    @forelse ($unreadNotifications ?? [] as $notification)
                        <a href="{{ route('notifications.open', $notification->id) }}" class="flex gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50">
                            <span class="text-xl">{{ $notification->data['icon'] ?? '🔔' }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium">{{ $notification->data['title'] ?? 'Notificación' }}</span>
                                <span class="block text-xs muted">{{ \Illuminate\Support\Str::limit($notification->data['message'] ?? '', 80) }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                        </a>
                    @empty
                        <p class="px-4 py-6 text-center text-sm muted">No tienes notificaciones nuevas 🎉</p>
                    @endforelse
                </div>
                <a href="{{ route('notifications.index') }}" class="block border-t border-slate-100 px-4 py-2 text-center text-sm link dark:border-slate-700">Ver todas</a>
            </x-slot>
        </x-dropdown>

        {{-- Menú de usuario --}}
        <x-dropdown align="right" width="w-56" content-classes="bg-white py-1 dark:bg-slate-800">
            <x-slot name="trigger">
                <button type="button" class="flex items-center gap-2 rounded-xl p-1 hover:bg-slate-100 dark:hover:bg-slate-800">
                    <x-avatar :user="auth()->user()" class="h-8 w-8" />
                    <span class="hidden max-w-[10rem] truncate text-sm font-medium md:block">{{ auth()->user()->name }}</span>
                    <x-heroicon-o-chevron-down class="hidden h-4 w-4 text-slate-400 md:block" />
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="px-4 py-2">
                    <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs muted">{{ auth()->user()->email }}</p>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-700"></div>
                <x-dropdown-link :href="route('profile.edit')">
                    <x-heroicon-o-user-circle class="h-4 w-4" /> Perfil y seguridad
                </x-dropdown-link>
                <x-dropdown-link :href="route('achievements.index')">
                    <x-heroicon-o-star class="h-4 w-4" /> Mis logros
                </x-dropdown-link>
                @if (auth()->user()->isAdmin())
                    <x-dropdown-link :href="route('admin.dashboard')">
                        <x-heroicon-o-shield-check class="h-4 w-4" /> Administración
                    </x-dropdown-link>
                @endif
                <div class="border-t border-slate-100 dark:border-slate-700"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        <x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" /> Cerrar sesión
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
