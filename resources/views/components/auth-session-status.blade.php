@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300']) }}>
        {{ $status }}
    </div>
@endif
