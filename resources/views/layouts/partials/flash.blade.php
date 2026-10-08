@php
    $statusMessages = [
        'profile-updated' => 'Perfil actualizado.',
        'password-updated' => 'Contraseña actualizada.',
        'preferences-updated' => 'Preferencias guardadas.',
        'session-closed' => 'Sesión cerrada.',
        'sessions-closed' => 'Se cerraron las sesiones de otros dispositivos.',
        'verification-code-sent' => 'Te enviamos un nuevo código de verificación.',
    ];
    $toasts = collect([
        session('success') ? ['type' => 'success', 'message' => session('success')] : null,
        session('error') ? ['type' => 'error', 'message' => session('error')] : null,
        isset($statusMessages[session('status')]) ? ['type' => 'success', 'message' => $statusMessages[session('status')]] : null,
    ])->filter()->values();
@endphp

{{-- Notificaciones emergentes (flash del servidor y eventos "toast" de JavaScript) --}}
<div x-data="{
        toasts: @js($toasts),
        add(toast) { toast.id = Date.now() + Math.random(); this.toasts.push(toast); setTimeout(() => this.remove(toast.id), 5000) },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) },
     }"
     x-init="toasts.forEach(t => { t.id = Math.random(); setTimeout(() => remove(t.id), 6000) })"
     @toast.window="add($event.detail)"
     class="pointer-events-none fixed inset-x-4 top-20 z-[60] flex flex-col items-end gap-2 sm:left-auto sm:right-6 sm:w-96">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-transition.opacity class="pointer-events-auto flex w-full items-start gap-3 rounded-2xl border bg-white p-4 shadow-lg dark:bg-slate-800"
             :class="toast.type === 'error' ? 'border-rose-200 dark:border-rose-500/40' : 'border-emerald-200 dark:border-emerald-500/40'">
            <span class="text-lg" x-text="toast.type === 'error' ? '⚠️' : '✅'"></span>
            <p class="flex-1 text-sm" x-text="toast.message"></p>
            <button type="button" class="text-slate-400 hover:text-slate-600" @click="remove(toast.id)" aria-label="Cerrar">
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>

{{-- Celebración de insignias nuevas --}}
@if (session('newBadges'))
    <div x-data="{ open: true }" x-show="open" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-sm" @keydown.escape.window="open = false">
        <div class="card w-full max-w-sm animate-pop-in p-6 text-center" @click.outside="open = false">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">¡Logro desbloqueado!</p>
            <div class="mt-4 space-y-4">
                @foreach (session('newBadges') as $badge)
                    <div>
                        <div class="text-5xl">{{ $badge['icon'] }}</div>
                        <p class="mt-2 text-lg font-bold">{{ $badge['name'] }}</p>
                        <p class="text-sm muted">{{ $badge['description'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 flex justify-center gap-2">
                <a href="{{ route('achievements.index') }}" class="btn-secondary">Ver logros</a>
                <button type="button" class="btn-primary" @click="open = false">¡Genial!</button>
            </div>
        </div>
    </div>
@endif
