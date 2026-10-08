<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system" class="h-full">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <div class="grid min-h-full lg:grid-cols-2">
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-brand-600 via-violet-600 to-fuchsia-600 p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-xl font-bold">{{ config('app.name') }}</span>
                </a>
                <div class="max-w-md space-y-6">
                    <h2 class="text-4xl font-bold leading-tight">Convierte tus apuntes en cuestionarios con IA.</h2>
                    <ul class="space-y-3 text-white/90">
                        <li class="flex gap-3"><span>📄</span> Escanea PDF, imágenes y notas manuscritas.</li>
                        <li class="flex gap-3"><span>🧠</span> Preguntas MCQ y SATA con explicaciones.</li>
                        <li class="flex gap-3"><span>🏆</span> Rachas, insignias y tabla de clasificación.</li>
                        <li class="flex gap-3"><span>🗓️</span> Planificador de estudio automático.</li>
                    </ul>
                </div>
                <p class="text-sm text-white/70">Impulsado por Google Gemini AI</p>
                <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
            </div>

            <div class="flex flex-col items-center justify-center px-4 py-10 sm:px-8">
                <a href="{{ route('home') }}" class="mb-8 flex items-center gap-2 lg:hidden">
                    <x-application-logo class="h-10 w-10" />
                    <span class="text-xl font-bold">{{ config('app.name') }}</span>
                </a>
                <div class="w-full max-w-md">
                    <div class="card card-body">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
