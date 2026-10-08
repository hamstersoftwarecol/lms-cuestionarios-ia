<x-app-layout :title="$announcement->exists ? 'Editar anuncio' : 'Nuevo anuncio'">
    <x-page-header :title="$announcement->exists ? 'Editar anuncio' : 'Nuevo anuncio'" />

    <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="card card-body max-w-3xl space-y-4">
        @csrf
        @if ($announcement->exists) @method('PUT') @endif

        <div>
            <x-input-label for="title" value="Título" />
            <x-text-input id="title" name="title" :value="old('title', $announcement->title)" required maxlength="150" />
            <x-input-error :messages="$errors->get('title')" />
        </div>
        <div>
            <x-input-label for="body" value="Mensaje" />
            <textarea id="body" name="body" rows="4" class="input" required maxlength="2000">{{ old('body', $announcement->body) }}</textarea>
            <x-input-error :messages="$errors->get('body')" />
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <x-input-label for="type" value="Tipo" />
                <select id="type" name="type" class="input">
                    @foreach (\App\Models\Announcement::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $announcement->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-input-label for="starts_at" value="Visible desde (opcional)" />
                <x-text-input id="starts_at" type="datetime-local" name="starts_at" :value="old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i'))" />
            </div>
            <div>
                <x-input-label for="ends_at" value="Visible hasta (opcional)" />
                <x-text-input id="ends_at" type="datetime-local" name="ends_at" :value="old('ends_at', $announcement->ends_at?->format('Y-m-d\TH:i'))" />
                <x-input-error :messages="$errors->get('ends_at')" />
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" class="checkbox" @checked(old('is_active', $announcement->is_active))> Activo</label>
        @unless ($announcement->exists)
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="notify" value="1" class="checkbox" checked> Enviar también como notificación a todos los usuarios</label>
        @endunless
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.announcements.index') }}" class="btn-ghost">Cancelar</a>
            <x-primary-button>{{ $announcement->exists ? 'Guardar' : 'Publicar' }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
