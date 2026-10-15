<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Script rápido para evitar parpadeo de tema al cargar -->
        <script>
            (() => {
                const theme = localStorage.getItem('color-theme');

                if (
                    theme === 'dark' ||
                    (
                        theme === null &&
                        window.matchMedia('(prefers-color-scheme: dark)').matches
                    )
                ) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <!-- Scripts y estilos de Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased overflow-x-hidden">
        <!-- Contenedor principal con fondo adaptable -->
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">

            <!-- Destellos encapsulados limpiamente -->
            <x-background-glow />

            <!-- Contenido de la Aplicación -->
            <div class="relative z-10">
                @include('layouts.navigation')

                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md shadow transition-colors duration-300">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                            <div>
                                {{ $header }}
                            </div>

                            <!-- Botón de cambio de tema controlado por Alpine.js -->
                            <div x-data="themeSwitcher()">
                                <button
                                    @click="toggle()"
                                    type="button"
                                    class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700
                                        text-gray-700 dark:text-gray-300
                                        focus:outline-none transition-colors"
                                    aria-label="Cambiar tema"
                                >
                                    <!-- Icono del Sol (Se muestra cuando está en modo oscuro para cambiar a claro) -->
                                    <svg
                                        x-show="dark"
                                        class="w-5 h-5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        x-cloak
                                    >
                                        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                                    </svg>

                                    <!-- Icono de la Luna (Se muestra cuando está en modo claro para cambiar a oscuro) -->
                                    <svg
                                        x-show="!dark"
                                        class="w-5 h-5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        x-cloak
                                    >
                                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
