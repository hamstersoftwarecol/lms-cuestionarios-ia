<x-app-layout title="Perfil y seguridad">
    <x-page-header title="Perfil y seguridad" subtitle="Gestiona tus datos, preferencias, contraseña y sesiones activas." />

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card card-body">@include('profile.partials.update-profile-information-form')</div>
        <div class="card card-body">@include('profile.partials.preferences-form')</div>
        <div class="card card-body">@include('profile.partials.update-password-form')</div>
        <div class="card card-body">@include('profile.partials.sessions')</div>
        <div class="card card-body lg:col-span-2">@include('profile.partials.delete-user-form')</div>
    </div>
</x-app-layout>
