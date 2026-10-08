<x-app-layout title="Nuevo plan de estudio">
    <x-page-header title="Nuevo plan de estudio" subtitle="Generaremos tareas diarias de estudio, autoevaluación, repaso espaciado y un simulacro final." />

    <form method="POST" action="{{ route('study-plans.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        <div class="card card-body space-y-5 lg:col-span-2">
            <div>
                <x-input-label for="title" value="Nombre del examen" />
                <x-text-input id="title" name="title" :value="old('title')" required placeholder="Ej.: Parcial de Biología" maxlength="255" />
                <x-input-error :messages="$errors->get('title')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <x-input-label for="start_date" value="Empiezo el" />
                    <x-text-input id="start_date" type="date" name="start_date" :value="old('start_date', today()->toDateString())" :min="today()->toDateString()" required />
                    <x-input-error :messages="$errors->get('start_date')" />
                </div>
                <div>
                    <x-input-label for="exam_date" value="Fecha del examen" />
                    <x-text-input id="exam_date" type="date" name="exam_date" :value="old('exam_date', today()->addWeeks(2)->toDateString())" :min="today()->addDay()->toDateString()" required />
                    <x-input-error :messages="$errors->get('exam_date')" />
                </div>
                <div>
                    <x-input-label for="daily_minutes" value="Minutos al día" />
                    <x-text-input id="daily_minutes" type="number" name="daily_minutes" :value="old('daily_minutes', 60)" min="15" max="600" step="5" required />
                    <x-input-error :messages="$errors->get('daily_minutes')" />
                </div>
            </div>
            <div>
                <x-input-label for="description" value="Notas (opcional)" />
                <textarea id="description" name="description" rows="2" class="input">{{ old('description') }}</textarea>
            </div>
            <div>
                <x-input-label for="topics" value="Temas adicionales (uno por línea o separados por comas)" />
                <textarea id="topics" name="topics" rows="4" class="input" placeholder="Respiración celular&#10;Genética mendeliana">{{ old('topics') }}</textarea>
                <x-input-error :messages="$errors->get('topics')" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title">Documentos a estudiar</h2>
                <p class="mt-1 text-sm muted">Cada documento se convierte en un tema. Si tiene cuestionario, se enlazará en las autoevaluaciones.</p>
                <div class="mt-3 max-h-72 space-y-2 overflow-y-auto">
                    @forelse ($notes as $note)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                            <input type="checkbox" name="notes[]" value="{{ $note->id }}" class="checkbox" @checked(in_array($note->id, old('notes', [])))>
                            <span class="truncate">{{ $note->title }}</span>
                        </label>
                    @empty
                        <p class="text-sm muted">No tienes documentos. Puedes escribir los temas manualmente.</p>
                    @endforelse
                </div>
                <x-input-error :messages="$errors->get('notes')" />
            </div>
            <x-primary-button class="w-full py-3"><x-heroicon-o-sparkles class="h-5 w-5" /> Generar plan</x-primary-button>
        </div>
    </form>
</x-app-layout>
