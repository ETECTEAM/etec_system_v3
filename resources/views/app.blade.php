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
                    background: #000;
                }

                @keyframes js-flicker {
                    0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% { opacity: 1; }
                    20%, 22%, 24%, 55% { opacity: 0.3; }
                }
                @keyframes js-pulse-border {
                    0%, 100% { box-shadow: 0 0 25px rgba(255,0,0,0.35), inset 0 0 40px rgba(255,0,0,0.15); }
                    50% { box-shadow: 0 0 55px rgba(255,0,0,0.7), inset 0 0 70px rgba(255,0,0,0.35); }
                }
                @keyframes js-scan {
                    0% { transform: translateY(-100%); }
                    100% { transform: translateY(100%); }
                }
                @keyframes js-shake {
                    0%, 100% { transform: translate(0, 0); }
                    10% { transform: translate(-1px, 1px); }
                    30% { transform: translate(1px, -1px); }
                    50% { transform: translate(-1px, -1px); }
                    70% { transform: translate(1px, 1px); }
                    90% { transform: translate(-1px, 1px); }
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
                    font-family: "Poppins", "Siemreap", Arial, sans-serif;
                    text-align: center;
                    color: #fff;
                    background: radial-gradient(circle at top, #1a0000, #000 75%);
                    overflow: hidden;
                }

                #javascript-required::before {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: repeating-linear-gradient(
                        0deg,
                        rgba(255,0,0,0.04) 0px,
                        rgba(255,0,0,0.04) 1px,
                        transparent 1px,
                        transparent 3px
                    );
                    pointer-events: none;
                }

                #javascript-required .js-card {
                    position: relative;
                    max-width: 500px;
                    width: 100%;
                    background: rgba(20,0,0,0.7);
                    border: 1px solid rgba(255,0,0,0.4);
                    border-radius: 8px;
                    padding: 48px 36px;
                    overflow: hidden;
                    animation: js-pulse-border 2s ease-in-out infinite, js-shake 6s ease-in-out infinite;
                }

                #javascript-required .js-card::after {
                    content: '';
                    position: absolute;
                    left: 0;
                    right: 0;
                    height: 40%;
                    background: linear-gradient(rgba(255,0,0,0) 0%, rgba(255,0,0,0.15) 50%, rgba(255,0,0,0) 100%);
                    animation: js-scan 3s linear infinite;
                }

                #javascript-required .js-emoji {
                    font-size: 64px;
                    display: block;
                    margin-bottom: 18px;
                    filter: drop-shadow(0 0 14px rgba(255,0,0,0.8));
                    animation: js-flicker 3.5s infinite;
                }

                #javascript-required h1 {
                    font-size: 24px;
                    margin: 0 0 14px;
                    line-height: 1.5;
                    color: #ff3b3b;
                    text-shadow: 0 0 10px rgba(255,0,0,0.7);
                    letter-spacing: 0.5px;
                    animation: js-flicker 4s infinite;
                }

                #javascript-required p {
                    font-size: 15px;
                    color: #ffb3b3;
                    margin: 0;
                    line-height: 1.7;
                }

                #javascript-required .js-sub {
                    margin-top: 22px;
                    font-size: 12px;
                    color: #ff3b3b;
                    opacity: 0.7;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                }
            </style>

            <div id="javascript-required">
                <div class="js-card">
                    <span class="js-emoji">👁️</span>
                    <h1>JavaScript ត្រូវបានបិទ</h1>
                    <p>គេហទំព័រនេះមិនអាចដំណើរការបានទេ ដរាបណា JavaScript មិនត្រូវបានបើក។ សូមបើកវា ហើយ Refresh ភ្លាមៗ។</p>
                    <div class="js-sub">Access denied</div>
                </div>
            </div>
        </noscript>
        @inertia
    </body>
</html>