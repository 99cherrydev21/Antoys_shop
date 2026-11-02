<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Antoys
        </h1>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Inicia sesión en tu cuenta
        </p>
    </div>

    <x-auth-session-status
        class="mb-4"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Teléfono -->
        <div>
            <x-input-label for="phone" value="Teléfono" />

            <x-text-input
                id="phone"
                class="block mt-1 w-full"
                type="tel"
                name="phone"
                :value="old('phone')"
                required
                autofocus
                autocomplete="username"
            />

            <x-input-error
                :messages="$errors->get('phone')"
                class="mt-2"
            />
        </div>

        <!-- Contraseña con botón de mostrar/ocultar (Ojito) -->
        <div class="mt-4" x-data="{ showPassword: false }">
            <x-input-label
                for="password"
                value="Contraseña"
            />

            <div class="relative mt-1">
                <x-text-input
                    id="password"
                    class="block w-full pr-10"
                    ::type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    autocomplete="current-password"
                />

                {{-- Botón interactivo para alternar visibilidad --}}
                <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none"
                    aria-label="Mostrar u ocultar contraseña"
                >
                    {{-- Icono Ojo Abierto (se muestra cuando showPassword es TRUE, es decir, cuando se ve la contraseña) --}}
                    <svg
                        x-show="showPassword"
                        x-cloak
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>

                    {{-- Icono Ojo Tachado (se muestra cuando showPassword es FALSE, es decir, contraseña oculta) --}}
                    <svg
                        x-show="!showPassword"
                        x-cloak
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor"
                        class="w-5 h-5"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <x-input-error
                :messages="$errors->get('password')"
                class="mt-2"
            />
        </div>

        <!-- Recordar sesión -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input
                    id="remember_me"
                    type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember"
                >

                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                    Recordarme
                </span>
            </label>
        </div>

        <!-- Botón de inicio de sesión -->
        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
