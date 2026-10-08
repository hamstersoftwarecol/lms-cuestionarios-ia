<x-mail::message>
# Hola, {{ $user->name }} 👋

Usa este código para verificar tu correo electrónico en **{{ config('app.name') }}**:

<x-mail::panel>
<div style="font-size: 32px; font-weight: 700; letter-spacing: 8px; text-align: center;">{{ $code }}</div>
</x-mail::panel>

El código caduca en **{{ $minutes }} minutos**. Si no creaste una cuenta, puedes ignorar este mensaje.

Saludos,<br>
El equipo de {{ config('app.name') }}
</x-mail::message>
