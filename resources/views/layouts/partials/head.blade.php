<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#4f46e5">

<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ config('app.name', 'LMS IA') }}</title>

<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">

{{-- Aplica el tema antes de pintar la página para evitar el parpadeo claro/oscuro. --}}
<script>
    (function () {
        var root = document.documentElement;
        var theme = root.dataset.theme || 'system';
        @guest
            try { theme = localStorage.getItem('lms-theme') || theme; } catch (e) {}
        @endguest
        root.dataset.theme = theme;
        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        }
    })();
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
