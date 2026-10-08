{{-- Reproductor de texto a voz con selector de las 8 voces de Gemini. --}}
@props(['source' => null, 'text' => null, 'voice' => null, 'compact' => false])

@php($voices = config('lms.tts.voices'))

<div x-data="ttsPlayer({ url: @js(route('tts')), voice: @js($voice ?? auth()->user()->tts_voice), source: @js($source), text: @js($text ?? '') })"
     {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-2']) }}>
    <button type="button" @click="toggle()" :disabled="loading" class="{{ $compact ? 'btn-ghost btn-sm' : 'btn-secondary btn-sm' }}" :title="playing ? 'Detener' : 'Escuchar'">
        <svg x-show="loading" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
        <x-heroicon-o-speaker-wave class="h-4 w-4" x-show="! loading && ! playing" />
        <x-heroicon-o-stop class="h-4 w-4" x-show="! loading && playing" x-cloak />
        @unless ($compact)
            <span x-text="loading ? 'Generando voz…' : (playing ? 'Detener' : 'Escuchar')">Escuchar</span>
        @endunless
    </button>
    @unless ($compact)
        <select x-model="voice" @change="stop()" class="input w-auto py-1 text-xs" aria-label="Voz">
            @foreach ($voices as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    @endunless
    <span x-show="usingBrowser && playing" x-cloak class="text-xs muted">Voz del navegador</span>
    <span x-show="error && ! usingBrowser" x-text="error" x-cloak class="text-xs text-rose-500"></span>
</div>
