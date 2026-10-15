
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Antoys') }}</title>

        <!-- Aplicar el tema antes de cargar los estilos -->
        <script>
            (() => {
                const theme = localStorage.getItem('color-theme');

                const dark = theme === 'dark' ||
                    (
                        theme === null &&
                        window.matchMedia('(prefers-color-scheme: dark)').matches
                    );

                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        <!-- Evitar que elementos Alpine aparezcan antes de inicializarse -->
        <style>
            [x-cloak] {
                display: none !important;
            }
        </style>

        <!-- Fuentes -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link
            href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap"
            rel="stylesheet"
        />

        <!-- Scripts y estilos -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 transition-colors duration-300">

        <!-- Fondo de la página -->
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900 transition-colors duration-300">

            <!-- Logo -->
            <div>
                <a href="/" aria-label="Ir al inicio">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500 dark:text-gray-400" />
                </a>
            </div>

            <!-- Tarjeta del login -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-md overflow-hidden sm:rounded-lg transition-colors duration-300">

                {{ $slot }}

            </div>
        </div>

    </body>
</html>
