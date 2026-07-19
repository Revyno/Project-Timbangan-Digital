<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Timbangan Digital') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/images/logo.png">
        <link rel="shortcut icon" type="image/png" href="/images/logo.png">

        <!-- Fonts: preconnect + preload for LCP -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link rel="preload" href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"></noscript>

        <!-- PWA Meta Tags -->
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#5c4a42">
        <link rel="apple-touch-icon" href="/images/logo.png">
        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js');
                });
            }
        </script> -->
    </body>
</html>
