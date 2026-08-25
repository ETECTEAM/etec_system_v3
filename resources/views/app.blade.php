<!DOCTYPE html>
<html lang="en" class="js-loading">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <style>
            .js-loading #app {
                display: none;
            }
        </style>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Siemreap&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css','resources/js/app.js'])
        <title>{{ env('APP_NAME') }}</title>
        @inertiaHead
    </head>
    <body>
        <noscript>
            <style>
                html,
                body {
                    margin: 0;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                    background: #111;
                }

                #javascript-required {
                    position: fixed;
                    inset: 0;
                    z-index: 2147483647;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 30px;
                    box-sizing: border-box;
                    font-family: Arial, sans-serif;
                    text-align: center;
                    background: #111;
                    color: #fff;
                }
            </style>

            <div id="javascript-required">
                <div>
                    <h1>Bit JavaScript Ter Ey</h1>
                    <p>Hot Mes</p>
                </div>
            </div>
        </noscript>
        @inertia
    </body>
</html>
