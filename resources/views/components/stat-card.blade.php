@props(['label', 'value', 'icon' => null, 'color' => 'indigo', 'hint' => null])

<div {{ $attributes->merge(['class' => 'card flex items-center gap-3 p-4 sm:gap-4 sm:p-6']) }}>
    @if ($icon)
        <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-sm min-[420px]:flex {{ \App\Support\Palette::gradient($color) }}">
            <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-6 w-6" />
        </div>
    @endif
    <div class="min-w-0">
        <p class="text-xs leading-tight muted sm:text-sm">{{ $label }}</p>
        <p class="mt-0.5 text-xl font-bold tracking-tight sm:text-2xl">{{ $value }}</p>
        @if ($hint)
            <p class="line-clamp-2 text-xs muted">{{ $hint }}</p>
        @endif
    </div>
</div>
