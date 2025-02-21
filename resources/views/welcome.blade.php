<!DOCTYPE html>
<html class="h-full">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="/assets/taskflow.svg" type="image/svg+xml">
    <title>Taskflow</title>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite('resources/js/app.js')
    @inertiaHead
</head>
<body class="h-full antialiased">
@inertia
</body>
</html>
