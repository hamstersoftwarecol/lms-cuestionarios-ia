<x-app-layout title="Nuevo documento">
    <x-page-header title="Nuevo documento" subtitle="Sube un archivo para escanearlo con IA o escribe/pega tus apuntes." />

    @unless ($aiEnabled)
        <div class="rounded-2xl border px-4 py-3 text-sm {{ \App\Support\Palette::alert('warning') }}">
            <strong>IA no configurada.</strong> Los PDF con texto, Word y archivos de texto funcionan sin IA; para escanear imágenes,
            fotos de apuntes o PDF escaneados añade <code>GEMINI_API_KEY</code> en el archivo <code>.env</code>.
        </div>
    @endunless

    <form method="POST" action="{{ route('notes.store') }}" enctype="multipart/form-data"
          x-data="{ mode: @js(old('content') ? 'text' : 'upload'), fileName: '', dragging: false, submitting: false }"
          @submit="submitting = true" class="card card-body space-y-6">
        @csrf

        <div class="inline-flex rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
            <button type="button" @click="mode = 'upload'" :class="mode === 'upload' ? 'bg-white shadow dark:bg-slate-700' : 'muted'" class="rounded-lg px-4 py-1.5 text-sm font-medium">📄 Subir archivo</button>
            <button type="button" @click="mode = 'text'" :class="mode === 'text' ? 'bg-white shadow dark:bg-slate-700' : 'muted'" class="rounded-lg px-4 py-1.5 text-sm font-medium">✍️ Escribir texto</button>
        </div>

        <div>
            <x-input-label for="title" value="Título (opcional si subes un archivo)" />
            <x-text-input id="title" name="title" :value="old('title')" placeholder="Ej.: Tema 3 · La célula" maxlength="255" />
            <x-input-error :messages="$errors->get('title')" />
        </div>

        <div x-show="mode === 'upload'">
            <label for="document"
                   @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                   @drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name"
                   :class="dragging ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-slate-300 dark:border-slate-700'"
                   class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-12 text-center transition hover:border-brand-400">
                <x-heroicon-o-cloud-arrow-up class="h-12 w-12 text-brand-500" />
                <p class="mt-3 font-medium" x-text="fileName || 'Arrastra tu archivo aquí o haz clic para elegirlo'"></p>
                <p class="mt-1 text-xs muted">PDF, JPG, PNG, WEBP, HEIC, DOCX, TXT o MD · máximo {{ round(config('lms.max_upload_kb') / 1024) }} MB</p>
                <p class="mt-1 text-xs muted">📸 ¿Apuntes a mano? Hazles una foto con el móvil: la IA los transcribe.</p>
                <input id="document" x-ref="file" type="file" name="document" class="sr-only"
                       accept=".{{ implode(',.', config('lms.allowed_extensions')) }}"
                       @change="fileName = $event.target.files[0]?.name">
            </label>
            <x-input-error :messages="$errors->get('document')" />
        </div>

        <div x-show="mode === 'text'" x-cloak>
            <x-input-label for="content" value="Contenido" />
            <textarea id="content" name="content" rows="14" class="input font-mono text-sm" placeholder="Pega aquí tus apuntes…">{{ old('content') }}</textarea>
            <x-input-error :messages="$errors->get('content')" />
        </div>

        @include('notes._fields', ['note' => null])

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
            <a href="{{ route('notes.index') }}" class="btn-ghost">Cancelar</a>
            <button class="btn-primary" :disabled="submitting">
                <svg x-show="submitting" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <span x-text="submitting ? (mode === 'upload' ? 'Escaneando con IA…' : 'Guardando…') : (mode === 'upload' ? 'Escanear y guardar' : 'Guardar nota')"></span>
            </button>
        </div>
    </form>
</x-app-layout>
