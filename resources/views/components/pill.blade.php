@props(['color' => 'slate'])

<span {{ $attributes->merge(['class' => 'pill '.\App\Support\Palette::pill($color)]) }}>{{ $slot }}</span>
