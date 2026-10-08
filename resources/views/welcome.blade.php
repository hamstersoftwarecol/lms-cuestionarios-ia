@php
    $features = [
        ['📄', 'Escaneo de documentos con IA', 'PDF, imágenes, Word o fotos de apuntes manuscritos: Gemini extrae el texto por ti.'],
        ['🧠', 'Cuestionarios MCQ + SATA', 'Preguntas de opción múltiple y de selección múltiple con explicaciones y niveles de dificultad.'],
        ['📊', 'Evaluación previa y posterior', 'Mide tu confianza y tu progreso antes y después de estudiar.'],
        ['🔊', 'Texto a voz con 8 voces', 'Escucha tus apuntes y preguntas con las voces de Gemini.'],
        ['🗓️', 'Planificador de estudio', 'Tareas diarias generadas automáticamente según la fecha de tu examen.'],
        ['👥', 'Grupos de estudio', 'Invita a tus compañeros con un código y compartid cuestionarios.'],
        ['🏆', 'Clasificación y logros', 'Tablas semanal, mensual e histórica, 9 insignias y rachas diarias.'],
        ['🛡️', 'Panel de administración', 'Analítica, usuarios, sesiones, auditoría, anuncios y copias de seguridad.'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme ?? 'system' }}" class="h-full scroll-smooth">
    <head>
        @include('layouts.partials.head', ['title' => 'Cuestionarios con IA'])
    </head>
    <body class="min-h-full bg-white font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <x-application-logo class="h-9 w-9" />
                    <span class="text-lg font-bold">{{ config('app.name') }}</span>
                </a>
                <nav class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">Ir a mi panel</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost">Iniciar sesión</a>
                        <a href="{{ route('register') }}" class="btn-primary">Empezar gratis</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-x-0 -top-40 -z-10 flex justify-center blur-3xl">
                    <div class="h-[28rem] w-[56rem] bg-gradient-to-tr from-brand-400 via-violet-400 to-fuchsia-300 opacity-30 dark:opacity-20"></div>
                </div>
                <div class="mx-auto max-w-6xl px-4 py-20 text-center sm:px-6 sm:py-28">
                    <span class="pill {{ \App\Support\Palette::pill('indigo') }}">✨ Impulsado por Google Gemini AI</span>
                    <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-extrabold tracking-tight sm:text-6xl">
                        Sube tus apuntes. <span class="bg-gradient-to-r from-brand-600 to-fuchsia-500 bg-clip-text text-transparent">La IA crea el examen.</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-2xl text-lg muted">
                        Un sistema de gestión del aprendizaje completo: escanea documentos, genera cuestionarios automáticamente,
                        planifica tu estudio y compite con tus compañeros. Sin escribir preguntas a mano.
                    </p>
                    <div class="mt-10 flex flex-wrap justify-center gap-3">
                        <a href="{{ auth()->check() ? route('notes.create') : route('register') }}" class="btn-primary px-6 py-3 text-base">Escanear mi primer documento</a>
                        <a href="#funciones" class="btn-secondary px-6 py-3 text-base">Ver funciones</a>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ([['1', 'Sube', 'Un PDF, una imagen o una foto de tus notas.'], ['2', 'Genera', 'Gemini crea preguntas con explicaciones en segundos.'], ['3', 'Aprende', 'Practica, sube en la clasificación y desbloquea logros.']] as [$step, $title, $text])
                        <div class="card card-body">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 font-bold text-white">{{ $step }}</span>
                            <h3 class="mt-4 font-semibold">{{ $title }}</h3>
                            <p class="mt-1 text-sm muted">{{ $text }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section id="funciones" class="border-t border-slate-200 bg-slate-50 py-20 dark:border-slate-800 dark:bg-slate-900/40">
                <div class="mx-auto max-w-6xl px-4 sm:px-6">
                    <h2 class="text-center text-3xl font-bold tracking-tight">Todo lo que necesita un LMS moderno</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-center muted">Ideal para docentes, estudiantes que preparan certificaciones, academias y equipos de formación.</p>
                    <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($features as [$icon, $title, $text])
                            <div class="card card-body">
                                <div class="text-3xl">{{ $icon }}</div>
                                <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                                <p class="mt-1 text-sm muted">{{ $text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-4xl px-4 py-20 text-center sm:px-6">
                <h2 class="text-3xl font-bold tracking-tight">¿Listo para estudiar de forma más inteligente?</h2>
                <p class="mt-3 muted">Crea tu cuenta con tu correo o con Google y genera tu primer cuestionario hoy.</p>
                <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="btn-primary mt-8 px-6 py-3 text-base">Comenzar ahora</a>
            </section>
        </main>

        <footer class="border-t border-slate-200 py-8 text-center text-sm muted dark:border-slate-800">
            © {{ date('Y') }} {{ config('app.name') }} · Laravel {{ app()->version() }} · Google Gemini
        </footer>
    </body>
</html>
