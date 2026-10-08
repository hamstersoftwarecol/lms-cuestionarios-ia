@props(['label', 'value', 'icon' => null, 'color' => 'indigo', 'hint' => null])

<div {{ $attributes->merge(['class' => 'card card-body flex items-center gap-4']) }}>
    @if ($icon)
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-sm {{ \App\Support\Palette::gradient($color) }}">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
        </div>
    @endif
    <div class="min-w-0">
        <p class="truncate text-sm muted">{{ $label }}</p>
        <p class="text-2xl font-bold tracking-tight">{{ $value }}</p>
        @if ($hint)
            <p class="truncate text-xs muted">{{ $hint }}</p>
        @endif
    </div>
</div>
