@props([
    'title',
    'subtitle'   => null,
    'back'       => null,
    'action',
    'lookup',
    'type'       => null,
    'limitTypes' => ['salida'],
    'labels'     => ['entrada' => 'Registrar entrada', 'salida' => 'Registrar salida', 'default' => 'Registrar'],
    'alpine'     => 'scannerList',   // nombre del componente Alpine (para extenderlo)
    'field'      => 'items',         // nombre del arreglo que se envía en el formulario
    'reason'     => true,            // mostrar descripción opcional
    'placeholder' => 'ANT-0000001',
])

@php
    $hasAside = isset($aside) && trim((string) $aside) !== '';

    $config = [
        'lookupUrl'  => $lookup,
        'type'       => $type ? old('type', $type) : null,
        'limitTypes' => $limitTypes,
        'labels'     => $labels,
    ];
@endphp

<div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
    <x-background-glow />

    <div class="relative z-10 max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 pb-28"
         x-data="{{ $alpine }}(@js($config))">

        <!-- Encabezado -->
        <div class="mb-6 flex items-center gap-3">
            @if ($back)
                <a href="{{ $back }}"
                   title="Volver"
                   aria-label="Volver"
                   class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                          border border-gray-300 bg-white text-gray-700 shadow-sm transition
                          hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-500
                          dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            <div class="min-w-0">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-700
                        dark:border-red-700 dark:bg-red-900 dark:text-red-200">
                <p class="font-semibold">No se pudo registrar la operación:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-5 rounded-2xl bg-green-100 p-4 text-sm font-medium text-green-800
                        dark:bg-green-900 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- enctype: necesario para subir la foto del comprobante en ventas --}}
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" @submit="validateBeforeSubmit($event)">
            @csrf

            <div class="grid grid-cols-1 gap-6 {{ $hasAside ? 'lg:grid-cols-3' : '' }}">

                @if ($hasAside)
                    <section class="lg:col-span-1 lg:sticky lg:top-6 lg:self-start">
                        {{ $aside }}
                    </section>
                @endif

                <section class="{{ $hasAside ? 'lg:col-span-2' : '' }}">

                    {{-- El input invisible de la zona es fixed z-30: todo lo que se toca va en z-40 o más --}}
                    <x-scanner.zone :placeholder="$placeholder" />

                    <x-scanner.list :field="$field">
                        @isset($card){{ $card }}@endisset
                    </x-scanner.list>

                    {{-- Contenido extra de cada vista (tipo de movimiento, popups de venta, etc.) --}}
                    {{ $slot }}

                    @if ($reason)
                        <div class="relative z-40 mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-lg
                                    dark:border-gray-700 dark:bg-gray-800">
                            <label for="reason"
                                   class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Descripción (opcional)
                            </label>
                            <textarea id="reason" name="reason" rows="2" maxlength="255"
                                      placeholder="Ej. Reposición de mercancía, venta o revisión de almacén..."
                                      class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm
                                             text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                             dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('reason') }}</textarea>
                        </div>
                    @endif

                    <!-- Botón principal (en ventas abre el popup de cobro) -->
                    <div class="sticky bottom-4 z-40 mt-4">
                        <div x-show="submitError" x-cloak x-transition
                             class="mb-3 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium
                                    text-red-700 shadow dark:border-red-700 dark:bg-red-900 dark:text-red-200"
                             x-text="submitError"></div>

                        <button type="submit"
                                :disabled="items.length === 0 || submitting"
                                class="w-full rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-5 py-3 font-semibold text-white shadow-lg
                                       transition-all duration-300 hover:from-blue-600 hover:to-teal-600 hover:shadow-xl hover:shadow-teal-500/30
                                       focus:outline-none focus:ring-2 focus:ring-teal-400 focus:ring-offset-2 dark:focus:ring-offset-gray-900
                                       disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:shadow-lg">
                            <span x-text="submitText"></span>
                        </button>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>
