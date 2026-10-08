<x-app-layout :title="$quiz->title">
    <div class="mx-auto max-w-3xl" x-data="quizRunner({ questions: @js($questions), remainingSeconds: @js($remainingSeconds) })">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="truncate text-sm muted">{{ $quiz->title }} · {{ $attempt->assessment_type->label() }}</p>
                <p class="font-semibold">Pregunta <span x-text="index + 1"></span> de <span x-text="questions.length"></span></p>
            </div>
            <div x-show="clock" x-cloak :class="remaining <= 60 ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' : 'bg-slate-100 dark:bg-slate-800'"
                 class="flex items-center gap-2 rounded-xl px-3 py-2 font-mono text-lg font-semibold">
                ⏱ <span x-text="clock"></span>
            </div>
        </div>

        <div class="mb-6 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-2 rounded-full bg-brand-600 transition-all duration-300" :style="`width: ${progress}%`"></div>
        </div>

        <div class="card card-body">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex gap-1.5">
                    <span class="pill" :class="current.type === 'sata' ? @js(\App\Support\Palette::pill('violet')) : @js(\App\Support\Palette::pill('indigo'))"
                          x-text="current.type === 'sata' ? 'Selecciona todas las que apliquen' : 'Una respuesta'"></span>
                    <span class="pill {{ \App\Support\Palette::pill('slate') }}" x-text="current.difficulty"></span>
                </div>
                {{-- Texto que lee la voz: enunciado y opciones de la pregunta actual --}}
                <span id="tts-question" class="sr-only" x-text="current.text + '. ' + current.options.map((o, i) => String.fromCharCode(65 + i) + ': ' + o).join('. ')"></span>
                <x-tts-button compact source="#tts-question" :voice="$voice" />
            </div>

            <h2 class="mt-4 text-lg font-semibold leading-snug sm:text-xl" x-text="current.text"></h2>

            <div class="mt-6 space-y-3">
                <template x-for="(option, i) in current.options" :key="current.id + '-' + i">
                    <button type="button" @click="toggle(i)"
                            :class="isSelected(i) ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-500/30 dark:bg-brand-500/10' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600'"
                            class="flex w-full items-start gap-3 rounded-2xl border p-4 text-left transition">
                        <span :class="[isSelected(i) ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300 dark:border-slate-600', current.type === 'sata' ? 'rounded-md' : 'rounded-full']"
                              class="flex h-7 w-7 shrink-0 items-center justify-center border-2 text-xs font-bold"
                              x-text="isSelected(i) ? '✓' : String.fromCharCode(65 + i)"></span>
                        <span class="pt-0.5" x-text="option"></span>
                    </button>
                </template>
            </div>

            <div class="mt-8 flex items-center justify-between gap-3">
                <button type="button" class="btn-secondary" @click="prev()" :disabled="index === 0"><x-heroicon-o-chevron-left class="h-4 w-4" /> Anterior</button>
                <button type="button" class="btn-primary" x-show="! isLast" @click="next()">Siguiente <x-heroicon-o-chevron-right class="h-4 w-4" /></button>
                <button type="button" class="btn-success" x-show="isLast" x-cloak @click="confirmSubmit()" :disabled="submitting">
                    <span x-text="submitting ? 'Enviando…' : 'Finalizar y corregir'"></span>
                </button>
            </div>
        </div>

        <div class="card card-body mt-6">
            <p class="text-sm font-medium">Navegación · <span class="muted" x-text="answeredCount + ' de ' + questions.length + ' respondidas'"></span></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <template x-for="(q, i) in questions" :key="q.id">
                    <button type="button" @click="goTo(i)"
                            :class="[i === index ? 'ring-2 ring-brand-500' : '', isAnswered(q) ? 'bg-brand-600 text-white' : 'bg-slate-100 dark:bg-slate-800']"
                            class="h-9 w-9 rounded-lg text-sm font-semibold" x-text="i + 1"></button>
                </template>
            </div>
            <button type="button" class="btn-ghost btn-sm mt-4 text-emerald-600" @click="confirmSubmit()" :disabled="submitting">Finalizar ahora</button>
        </div>

        <form x-ref="form" method="POST" action="{{ route('attempts.submit', $attempt) }}" class="hidden">
            @csrf
            <template x-for="q in questions" :key="'f' + q.id">
                <template x-for="option in answers[q.id]" :key="'f' + q.id + '-' + option">
                    <input type="hidden" :name="`answers[${q.id}][]`" :value="option">
                </template>
            </template>
        </form>
    </div>
</x-app-layout>
