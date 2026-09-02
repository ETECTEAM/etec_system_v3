<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#1A66FF">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="ETEC Center">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
        <script>
            // Set the `dark` class before first paint so the page never flashes
            // the wrong theme while Vue/Inertia boots.
            (function () {
                var stored = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                var isDark = stored === 'dark' || (stored !== 'light' && prefersDark);
                document.documentElement.classList.toggle('dark', isDark);
            })();
        </script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Siemreap&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css','resources/js/app.js'])
        <title>{{ env('APP_NAME') }}</title>
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
