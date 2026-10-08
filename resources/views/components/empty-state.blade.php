@props(['icon' => 'sparkles', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-12 text-center']) }}>
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
        <x-dynamic-component :component="'heroicon-o-'.$icon" class="h-7 w-7" />
    </div>
    <h3 class="mt-4 text-base font-semibold">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-md text-sm muted">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
