<x-app-layout :title="'Editar: '.$note->title">
    <x-page-header title="Editar documento" :subtitle="$note->title" />

    <form method="POST" action="{{ route('notes.update', $note) }}" class="card card-body space-y-6">
        @csrf @method('PUT')

        <div>
            <x-input-label for="title" value="Título" />
            <x-text-input id="title" name="title" :value="old('title', $note->title)" required maxlength="255" />
            <x-input-error :messages="$errors->get('title')" />
        </div>

        <div>
            <x-input-label for="content" value="Contenido (puedes corregir el texto extraído por la IA)" />
            <textarea id="content" name="content" rows="18" class="input font-mono text-sm" required>{{ old('content', $note->content) }}</textarea>
            <x-input-error :messages="$errors->get('content')" />
        </div>

        @include('notes._fields')

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
            <a href="{{ route('notes.show', $note) }}" class="btn-ghost">Cancelar</a>
            <x-primary-button>Guardar cambios</x-primary-button>
        </div>
    </form>
</x-app-layout>
