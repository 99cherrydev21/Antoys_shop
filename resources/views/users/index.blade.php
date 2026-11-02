@php
    // Textos en minúsculas para el filtro instantáneo del buscador
    $haystack = $users->getCollection()
        ->map(fn ($u) => mb_strtolower($u->name . ' ' . $u->phone . ' ' . ($u->role?->name ?? '')))
        ->values();

    $inputClasses = 'w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-gray-900 outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white';
@endphp

<x-app-layout>
    <div
        x-data="usersPage({
            search: @js((string) request('search', '')),
            haystack: @js($haystack),
            createOpen: @js(old('_form') === 'create'),
        })"
        class="relative min-h-screen bg-gray-100 py-8 pb-28 transition-colors duration-300 dark:bg-gray-900"
    >
        <x-background-glow />

        <div class="relative mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                    Gestión de usuarios
                </h1>

            </div>

            {{-- Buscador --}}
            <form method="GET" action="{{ route('users.index') }}" role="search">
                <label for="user-search" class="sr-only">Buscar usuarios</label>

                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <input
                        id="user-search"
                        type="search"
                        name="search"
                        x-model="q"
                        @keydown.escape="q = ''"
                        placeholder="Buscar por nombre, teléfono o rol"
                        autocomplete="off"
                        class="w-full rounded-2xl border border-gray-200 bg-white py-3 pl-12 pr-4 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </form>

            {{-- Mensajes --}}
            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                    {{ session('warning') }}
                </div>
            @endif

            {{-- Errores generales (los de registro/edición se muestran dentro de su ventana) --}}
            @if ($errors->any() && ! old('_form'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
                    <p class="font-semibold">No se pudieron guardar los cambios:</p>

                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Listado --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Personal registrado
                    </h2>

                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 sm:hidden">
                        Desliza a la derecha para editar y a la izquierda para activar o desactivar.
                    </p>
                </div>

                @forelse ($users as $user)
                    @php
                        $canToggle = (int) $user->id !== (int) auth()->id();
                        $isEditing = old('_form') === 'edit-' . $user->id;
                        $confirmText = $user->is_active
                            ? '¿Seguro que deseas desactivar esta cuenta?'
                            : '¿Deseas volver a activar esta cuenta?';
                    @endphp

                    <div
                        x-data="swipeRow({
                            canToggle: @js($canToggle),
                            confirmText: @js($confirmText),
                            openEdit: @js($isEditing),
                        })"
                        x-show="matches({{ $loop->index }})"
                        class="relative overflow-hidden border-b border-gray-200 last:border-b-0 dark:border-gray-700"
                    >

                        {{-- Capa de fondo que se revela al deslizar --}}
                        <div
                            aria-hidden="true"
                            class="absolute inset-0 flex items-center justify-between px-6 text-white"
                            :class="x > 0
                                ? 'bg-indigo-500'
                                : (x < 0 ? '{{ $user->is_active ? 'bg-red-500' : 'bg-green-500' }}' : 'bg-transparent')"
                        >
                            {{-- Deslizar a la derecha: editar --}}
                            <div x-show="x > 0"
                                 class="flex items-center gap-2 text-sm font-semibold transition-transform duration-150"
                                 :class="x >= threshold ? 'scale-110' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                                Editar
                            </div>

                            {{-- Deslizar a la izquierda: activar o desactivar --}}
                            <div x-show="x < 0"
                                 class="ml-auto flex items-center gap-2 text-sm font-semibold transition-transform duration-150"
                                 :class="x <= -threshold ? 'scale-110' : ''">
                                @if ($user->is_active)
                                    Desactivar
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                @else
                                    Activar
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </div>
                        </div>

                        {{-- Tarjeta deslizable --}}
                        <div
                            class="relative touch-pan-y bg-white p-4 dark:bg-gray-800 sm:p-5"
                            :class="dragging ? 'select-none' : 'transition-transform duration-200 ease-out'"
                            :style="`transform: translateX(${x}px)`"
                            @pointerdown="start($event)"
                            @pointermove="move($event)"
                            @pointerup="end()"
                            @pointercancel="cancel()"
                        >
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">

                                {{-- Avatar e información --}}
                                <div class="flex min-w-0 flex-1 items-center gap-3">

                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-lg font-bold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="break-words font-semibold text-gray-900 dark:text-white">
                                            {{ $user->name }}
                                        </p>

                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $user->phone }}
                                        </p>

                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300">
                                                {{ $user->role?->name ?? 'Sin rol' }}
                                            </span>

                                            @if ($user->is_active)
                                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                                    ● Activo
                                                </span>
                                            @else
                                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                                    ● Inactivo
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Botones de respaldo para pantallas grandes --}}
                                <div class="hidden flex-wrap gap-2 sm:flex">


                                    @if ($canToggle)

                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Formulario oculto que activa o desactiva la cuenta --}}
                        @if ($canToggle)
                            <form
                                x-ref="toggleForm"
                                method="POST"
                                action="{{ route('users.toggle-status', $user) }}"
                                class="hidden"
                            >
                                @csrf
                                @method('PATCH')
                            </form>
                        @endif

                        {{-- POPUP DE EDICIÓN --}}
                        <template x-teleport="body">
                            <div
                                x-cloak
                                x-show="open"
                                x-transition.opacity
                                @keydown.escape.window="open = false"
                                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
                                style="display: none;"
                                role="dialog"
                                aria-modal="true"
                                aria-labelledby="edit-title-{{ $user->id }}"
                            >
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="open = false"></div>

                                <div
                                    x-show="open"
                                    x-transition
                                    class="relative z-10 my-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                                    @click.stop
                                >
                                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                                        <div>
                                            <h2 id="edit-title-{{ $user->id }}" class="text-xl font-bold text-gray-900 dark:text-white">
                                                Editar usuario
                                            </h2>

                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                Modifica los datos de la cuenta.
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            @click="open = false"
                                            class="flex h-9 w-9 items-center justify-center rounded-full text-2xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                                            aria-label="Cerrar ventana"
                                        >
                                            &times;
                                        </button>
                                    </div>

                                    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5 p-5">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_form" value="edit-{{ $user->id }}">

                                        @if ($isEditing && $errors->any())
                                            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                                <p class="font-semibold">No se pudieron guardar los cambios:</p>
                                                <ul class="mt-1 list-inside list-disc">
                                                    @foreach ($errors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div>
                                            <label for="name-{{ $user->id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Nombre completo
                                            </label>
                                            <input
                                                id="name-{{ $user->id }}"
                                                type="text"
                                                name="name"
                                                value="{{ $isEditing ? old('name') : $user->name }}"
                                                required
                                                maxlength="255"
                                                autocomplete="name"
                                                class="{{ $inputClasses }}"
                                            >
                                        </div>

                                        <div>
                                            <label for="phone-{{ $user->id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Número de teléfono
                                            </label>
                                            <input
                                                id="phone-{{ $user->id }}"
                                                type="tel"
                                                name="phone"
                                                value="{{ $isEditing ? old('phone') : $user->phone }}"
                                                required
                                                maxlength="20"
                                                inputmode="tel"
                                                autocomplete="tel"
                                                class="{{ $inputClasses }}"
                                            >
                                        </div>

                                        <div>
                                            <label for="role-{{ $user->id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Rol del usuario
                                            </label>
                                            <select id="role-{{ $user->id }}" name="role_id" required class="{{ $inputClasses }}">
                                                @foreach ($roles as $role)
                                                    <option
                                                        value="{{ $role->id }}"
                                                        @selected((int) ($isEditing ? old('role_id') : $user->role_id) === (int) $role->id)
                                                    >
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @unless ($canToggle)
                                                <p class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                                    Por seguridad, no puedes cambiar tu propio rol.
                                                </p>
                                            @endunless
                                        </div>

                                        <div class="rounded-xl bg-gray-100 p-3 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-400">
                                            La contraseña actual no se modificará al guardar estos datos.
                                        </div>

                                        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                                            <button
                                                type="button"
                                                @click="open = false"
                                                class="rounded-xl border border-gray-300 px-5 py-3 font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                            >
                                                Cancelar
                                            </button>

                                            <button
                                                type="submit"
                                                class="rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700"
                                            >
                                                Guardar cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </template>

                    </div>

                @empty

                    <div class="px-6 py-14 text-center">
                        <div class="text-5xl">👥</div>

                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">
                            @if (request('search'))
                                Ningún usuario coincide con tu búsqueda
                            @else
                                No hay usuarios registrados
                            @endif
                        </h3>

                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            @if (request('search'))
                                Prueba con otro nombre, teléfono o rol.
                            @else
                                Registra el primer usuario para comenzar.
                            @endif
                        </p>

                        @unless (request('search'))
                            <button
                                type="button"
                                @click="createOpen = true"
                                class="mt-5 inline-flex rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white transition hover:bg-indigo-700"
                            >
                                Registrar usuario
                            </button>
                        @endunless
                    </div>

                @endforelse

                {{-- Sin coincidencias en el filtro instantáneo --}}
                <div x-cloak x-show="noMatches" class="px-6 py-10 text-center" style="display: none;">
                    <p class="font-semibold text-gray-900 dark:text-white">
                        Sin resultados para "<span x-text="q"></span>"
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Presiona Enter para buscar en todas las páginas.
                    </p>
                </div>

                {{-- Paginación --}}
                @if ($users->hasPages())
                    <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                        {{ $users->withQueryString()->links() }}
                    </div>
                @endif

            </div>
        </div>

        {{-- Botón flotante: registrar usuario --}}
        <div class="pointer-events-none fixed right-4 z-40 sm:right-6 lg:inset-x-0 lg:mx-auto lg:max-w-7xl lg:px-8"
             style="bottom: calc(5rem + env(safe-area-inset-bottom));">
            <div class="relative flex justify-end">
                <button
                    type="button"
                    @click="createOpen = true"
                    class="group pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full
                           bg-gradient-to-tr from-blue-200 via-teal-500 to-emerald-400
                           text-white shadow-xl shadow-blue-950/30 transition-all duration-300
                           hover:-translate-y-1 hover:scale-110 hover:from-blue-500 hover:via-teal-400 hover:to-emerald-300 hover:shadow-2xl hover:shadow-blue-500/40
                           focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2
                           dark:from-blue-700 dark:via-teal-600 dark:to-emerald-500
                           dark:hover:from-blue-200 dark:hover:via-teal-500 dark:hover:to-emerald-400
                           dark:focus:ring-offset-gray-900"
                    title="Registrar usuario"
                    aria-label="Registrar usuario"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- POPUP DE REGISTRO --}}
        <template x-teleport="body">
            <div
                x-cloak
                x-show="createOpen"
                x-transition.opacity
                @keydown.escape.window="createOpen = false"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4"
                style="display: none;"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-title"
            >
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="createOpen = false"></div>

                <div
                    x-show="createOpen"
                    x-transition
                    class="relative z-10 my-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                    @click.stop
                >
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m8-10a4 4 0 100-8 4 4 0 000 8zm8-1v6m-3-3h6" />
                                </svg>
                            </div>

                            <div>
                                <h2 id="create-title" class="text-xl font-bold text-gray-900 dark:text-white">
                                    Registrar empleado
                                </h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Se enviarán las credenciales temporales por WhatsApp.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="createOpen = false"
                            class="flex h-9 w-9 items-center justify-center rounded-full text-2xl text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                            aria-label="Cerrar ventana"
                        >
                            &times;
                        </button>
                    </div>

                    <form action="{{ route('users.store') }}" method="POST" class="space-y-5 p-5">
                        @csrf
                        <input type="hidden" name="_form" value="create">

                        @if ($errors->any() && old('_form') === 'create')
                            <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
                                <p class="font-semibold">No se pudo registrar al empleado:</p>
                                <ul class="mt-1 list-inside list-disc">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Nombre --}}
                        <div>
                            <label for="create-name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nombre completo *
                            </label>
                            <input
                                type="text"
                                id="create-name"
                                name="name"
                                value="{{ old('_form') === 'create' ? old('name') : '' }}"
                                required
                                maxlength="255"
                                autocomplete="name"
                                class="{{ $inputClasses }}"
                            >
                        </div>

                        {{-- Teléfono --}}
                        <div>
                            <label for="create-phone" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Número de WhatsApp *
                            </label>
                            <input
                                type="tel"
                                id="create-phone"
                                name="phone"
                                value="{{ old('_form') === 'create' ? old('phone') : '' }}"
                                required
                                maxlength="20"
                                inputmode="tel"
                                autocomplete="tel"
                                class="{{ $inputClasses }}"
                            >

                        </div>

                        {{-- Rol --}}
                        <div>
                            <label for="create-role" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Rol del empleado *
                            </label>
                            <select id="create-role" name="role_id" required class="{{ $inputClasses }}">
                                <option value="">Selecciona un rol</option>

                                @foreach ($roles as $role)
                                    <option
                                        value="{{ $role->id }}"
                                        @selected(old('_form') === 'create' && (string) old('role_id') === (string) $role->id)
                                    >
                                        {{ $role->name }}
                                        @if ($role->description)
                                            — {{ $role->description }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Aviso de WhatsApp --}}
                        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
                            <div class="flex items-start gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>

                                <div>
                                    <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                                        Envío automático de credenciales
                                    </p>

                                </div>
                            </div>
                        </div>

                        {{-- Botones --}}
                        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                @click="createOpen = false"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-5 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Registrar y enviar WhatsApp
                            </button>
                        </div>

                        <p class="text-center text-xs text-gray-500 dark:text-gray-400">
                            Los campos marcados con * son obligatorios.
                        </p>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            // Estado de la página: buscador y ventana de registro
            Alpine.data('usersPage', ({ search, haystack, createOpen }) => ({
                q: search,
                hay: haystack,
                createOpen,

                matches(i) {
                    const term = this.q.trim().toLowerCase();
                    return term === '' || (this.hay[i] ?? '').includes(term);
                },

                get noMatches() {
                    return this.q.trim() !== '' && this.hay.length > 0 && !this.hay.some((_, i) => this.matches(i));
                },
            }));

            // Fila deslizable: derecha = editar, izquierda = activar/desactivar
            Alpine.data('swipeRow', ({ canToggle, confirmText, openEdit }) => ({
                open: openEdit,
                x: 0,
                startX: 0,
                startY: 0,
                dragging: false,
                axis: null,
                threshold: 90,
                maxPull: 130,

                start(e) {
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    this.startX = e.clientX;
                    this.startY = e.clientY;
                    this.axis = null;
                    this.dragging = true;
                },

                move(e) {
                    if (!this.dragging) return;

                    const dx = e.clientX - this.startX;
                    const dy = e.clientY - this.startY;

                    if (this.axis === null) {
                        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
                        this.axis = Math.abs(dx) > Math.abs(dy) ? 'h' : 'v';
                        if (this.axis === 'h') e.currentTarget.setPointerCapture(e.pointerId);
                    }

                    if (this.axis !== 'h') return;

                    let next = Math.max(-this.maxPull, Math.min(this.maxPull, dx));
                    if (next < 0 && !canToggle) next = 0; // no puedes desactivarte a ti mismo
                    this.x = next;
                },

                end() {
                    if (!this.dragging) return;

                    const pulled = this.x;
                    const wasHorizontal = this.axis === 'h';

                    this.dragging = false;
                    this.axis = null;
                    this.x = 0;

                    if (!wasHorizontal) return;

                    if (pulled >= this.threshold) {
                        this.open = true;
                    } else if (pulled <= -this.threshold && canToggle) {
                        this.confirmToggle();
                    }
                },

                cancel() {
                    this.dragging = false;
                    this.axis = null;
                    this.x = 0;
                },

                confirmToggle() {
                    if (canToggle && confirm(confirmText)) {
                        this.$refs.toggleForm.submit();
                    }
                },
            }));
        });
    </script>
</x-app-layout>
