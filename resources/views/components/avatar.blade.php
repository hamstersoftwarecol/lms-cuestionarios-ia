@props(['user'])

@if ($user?->avatar)
    <img src="{{ $user->avatar }}" alt="{{ $user->name }}" referrerpolicy="no-referrer" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-violet-500 text-xs font-bold text-white']) }}>
        {{ $user?->initials() ?: '?' }}
    </span>
@endif
