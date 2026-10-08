@props(['value' => 0, 'color' => 'bg-brand-600', 'size' => 'h-2'])

<div {{ $attributes->merge(['class' => "w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800 {$size}"]) }}>
    <div class="{{ $size }} {{ $color }} rounded-full transition-all duration-500" style="width: {{ max(0, min(100, $value)) }}%"></div>
</div>
