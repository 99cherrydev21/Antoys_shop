
<nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-100 dark:border-gray-700 transition-colors duration-300 relative z-30">

    <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            <!-- LOGO + NOMBRE -->
            <div class="flex items-center gap-2 sm:gap-4 overflow-hidden">

                <!-- Enlace de inicio con el gatito -->
                <div class="flex items-center gap-1.5 shrink-0">
                    <a
                        href="{{ route('dashboard') }}"
                        class="p-1 rounded-md hover:bg-gray-200/50 dark:hover:bg-gray-700/50 transition flex items-center"
                        title="Inicio"
                        aria-label="Ir al inicio"
                    >
                        <x-application-logo class="block h-8 w-auto" />
                    </a>
                </div>

                <!-- Saludo y rol -->
                <span
                    class="text-base sm:text-xl font-semibold break-words"
                    style="background: linear-gradient(to right, #b9a78b, #607491); -webkit-background-clip: text; color: transparent;"
                >
                    Hola, {{ Auth::user()->name . ' - ' . (Auth::user()->role?->name ?? 'Sin rol asignado') }}
                </span>

            </div>

            <!-- CONTROLES DERECHA -->
            <div class="flex items-center gap-2 shrink-0">

                <!-- MENÚ HAMBURGUESA -->
                <div
                    class="relative"
                    x-data="{ open: false }"
                >

                    <!-- Botón para abrir/cerrar el menú -->
                    <button
                        @click="open = !open"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        type="button"
                        class="p-2 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none transition-colors"
                        aria-label="Abrir menú"
                        :aria-expanded="open.toString()"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            fill="currentColor"
                            viewBox="0 0 16 16"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M2.5 12a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1zm0-3a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1zm0-3a.5.5 0 0 1 0-1h11a.5.5 0 0 1 0 1z"
                            />
                        </svg>
                    </button>

                    <!-- MENÚ DESPLEGABLE -->
                    <div
                        x-show="open"
                        x-cloak
                        x-transition.origin.top.right
                        @click.outside="open = false"
                        class="absolute right-0 mt-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-2xl p-3 w-48 z-50 border border-gray-200/50 dark:border-gray-700/50 space-y-3"
                    >

                        <!-- CAMBIAR TEMA -->
                        <div
                            x-data="themeSwitcher()"
                            class="flex items-center justify-between px-2"
                        >
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Tema
                            </span>

                            <button
                                @click="toggle()"
                                type="button"
                                class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none transition-colors"
                                aria-label="Cambiar tema"
                                title="Cambiar tema claro u oscuro"
                            >
                                <!-- Sol: se muestra cuando el tema es oscuro -->
                                <svg
                                    x-show="dark"
                                    x-cloak
                                    class="w-5 h-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11H3a1 1 0 100 2h1a1 1 0 100-2z" />
                                </svg>

                                <!-- Luna: se muestra cuando el tema es claro -->
                                <svg
                                    x-show="!dark"
                                    x-cloak
                                    class="w-5 h-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                                </svg>
                            </button>
                        </div>

                        <hr class="border-gray-200 dark:border-gray-700">

                        <!-- OPCIONES DEL MENÚ -->
                        <div class="space-y-1">

                            <!-- Perfil -->
                            <x-responsive-nav-link
                                :href="route('profile.edit')"
                            >
                                {{ __('Perfil') }}
                            </x-responsive-nav-link>

                            <!-- Cerrar sesión -->
                            <form
                                method="POST"
                                action="{{ route('logout') }}"
                            >
                                @csrf

                                <button
                                    type="submit"
                                    class="w-full text-left px-3 py-2 text-sm text-red-600 dark:text-red-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                                >
                                    {{ __('Salir') }}
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
