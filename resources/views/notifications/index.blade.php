<x-app-layout title="Notificaciones">
    <x-page-header title="Notificaciones">
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button class="btn-secondary">Marcar todas como leídas</button>
        </form>
    </x-page-header>

    <div class="card divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.open', $notification->id) }}" @class(['flex gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/40', 'bg-brand-50/50 dark:bg-brand-500/5' => ! $notification->read_at])>
                <span class="text-2xl">{{ $notification->data['icon'] ?? '🔔' }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ $notification->data['title'] ?? 'Notificación' }}</p>
                    <p class="text-sm muted">{{ $notification->data['message'] ?? '' }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                </div>
                @unless ($notification->read_at)
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-brand-600"></span>
                @endunless
            </a>
        @empty
            <p class="px-6 py-12 text-center text-sm muted">No tienes notificaciones.</p>
        @endforelse
    </div>

    {{ $notifications->links() }}
</x-app-layout>
