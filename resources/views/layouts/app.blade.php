<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()?->theme ?? 'system' }}" class="h-full">
    <head>
        @include('layouts.partials.head')
        @stack('head')
    </head>
    <body class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        <div x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" class="min-h-full">
            <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden" @click="sidebar = false"></div>

            <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-200 dark:border-slate-800 dark:bg-slate-900 lg:w-64 lg:translate-x-0">
                @include('layouts.partials.sidebar')
            </aside>

            <div class="flex min-h-full flex-col lg:pl-64">
                @include('layouts.partials.topbar')

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-7xl space-y-6">
                        @include('layouts.partials.announcements')
                        {{ $slot }}
                    </div>
                </main>

                <footer class="px-4 pb-6 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                    {{ config('app.name') }} · Cuestionarios generados con Google Gemini AI
                </footer>
            </div>
        </div>

        @include('layouts.partials.flash')
        @stack('scripts')
    </body>
</html>
