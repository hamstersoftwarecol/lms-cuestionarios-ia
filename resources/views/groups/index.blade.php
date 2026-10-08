<x-app-layout title="Grupos de estudio">
    <x-page-header title="Grupos de estudio" subtitle="Estudia con tus compañeros: comparte cuestionarios y compite en la clasificación del grupo." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($groups as $group)
                <a href="{{ route('groups.show', $group) }}" class="card flex items-center gap-4 p-5 transition hover:shadow-md">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-violet-500 text-lg font-bold text-white">
                        {{ mb_strtoupper(mb_substr($group->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-semibold">{{ $group->name }}</p>
                            @if ($group->pivot->role === 'owner') <x-pill color="amber">Propietario</x-pill> @endif
                        </div>
                        <p class="truncate text-sm muted">{{ $group->description ?: 'Sin descripción' }}</p>
                        <p class="mt-1 text-xs muted">{{ $group->members_count }} miembros · {{ $group->quizzes_count }} cuestionarios · creado por {{ $group->owner->name }}</p>
                    </div>
                    <x-heroicon-o-chevron-right class="h-5 w-5 text-slate-400" />
                </a>
            @empty
                <x-empty-state icon="user-group" title="Aún no perteneces a ningún grupo" description="Crea un grupo e invita a tus compañeros con el código, o únete con el código que te compartan." />
            @endforelse
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('groups.join') }}" class="card card-body space-y-3">
                @csrf
                <h2 class="card-title">🔑 Unirme con un código</h2>
                <input name="code" value="{{ old('code', $code) }}" class="input text-center font-mono text-lg uppercase tracking-widest" placeholder="ABCD-1234" maxlength="20" required>
                <x-input-error :messages="$errors->get('code')" />
                <button class="btn-primary w-full">Unirme</button>
            </form>

            <form method="POST" action="{{ route('groups.store') }}" class="card card-body space-y-3">
                @csrf
                <h2 class="card-title">➕ Crear grupo</h2>
                <div>
                    <x-input-label for="name" value="Nombre" />
                    <x-text-input id="name" name="name" :value="old('name')" required maxlength="100" placeholder="Ej.: Biología 101" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div>
                    <x-input-label for="description" value="Descripción" />
                    <textarea id="description" name="description" rows="2" class="input" maxlength="500">{{ old('description') }}</textarea>
                </div>
                <div>
                    <x-input-label for="max_members" value="Máximo de miembros" />
                    <x-text-input id="max_members" type="number" name="max_members" :value="old('max_members', 50)" min="2" max="500" />
                </div>
                <button class="btn-secondary w-full">Crear grupo</button>
            </form>
        </div>
    </div>
</x-app-layout>
