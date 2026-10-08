{{-- Formulario con confirmación nativa: <x-confirm-form :action="..." method="DELETE" message="¿Seguro?"> --}}
@props(['action', 'method' => 'POST', 'message' => '¿Seguro que quieres continuar?'])

<form method="POST" action="{{ $action }}" onsubmit="return confirm(@js($message))" {{ $attributes }}>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    {{ $slot }}
</form>
