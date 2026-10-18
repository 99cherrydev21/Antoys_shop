<x-app-layout>
    <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Tarjeta contenedora principal (se oculta al imprimir si solo quieres que salga la etiqueta, o se mantiene si gustas) -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-8 border border-white/20 dark:border-gray-700/50 print:bg-none print:shadow-none print:border-none print:p-0">

                <!-- Encabezado de la vista -->
                <div class="mb-6 border-b border-gray-200 dark:border-gray-700 pb-4 no-print flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $product->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Detalles e impresión de etiqueta del producto.</p>
                    </div>
                </div>

                <!-- Información del producto en formato moderno -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8 no-print">
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                        <span class="block text-xs font-medium text-gray-400">ID</span>
                        <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ $product->sku }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                        <span class="block text-xs font-medium text-gray-400">Precio</span>
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">${{ number_format($product->price, 2) }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                        <span class="block text-xs font-medium text-gray-400">Existencias</span>
                        <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ $product->stock }}</span>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                        <span class="block text-xs font-medium text-gray-400">Estado</span>
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full font-semibold {{ $product->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                            {{ $product->is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>

                <!-- SECCIÓN DE LA ETIQUETA (Optimizada para impresión y vista previa) -->
                <div class="flex justify-center mb-8">
                    <div class="label bg-white text-gray-900 border border-gray-300 dark:border-gray-700 p-6 rounded-2xl w-72 text-center shadow-sm">
                        <h2 class="text-lg font-bold tracking-wider mb-1">Antoys</h2>
                        <p class="text-sm font-medium text-gray-700 mb-4 truncate">{{ $product->name }}</p>

                        <div class="flex justify-center mb-3">
                            <img
                                src="data:image/png;base64,{{ $barcode }}"
                                alt="Data Matrix del Id {{ $product->sku }}"
                                class="w-40 h-40 object-contain image-rendering-pixelated"
                            >
                        </div>

                        <p class="text-sm font-mono font-bold tracking-widest text-gray-600">{{ $product->sku }}</p>
                    </div>
                </div>

                <!-- Botones de Acción (Se ocultan al imprimir) -->
                <div class="no-print flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a
                        href="{{ route('products.index') }}"
                        class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition"
                    >
                        &larr; Volver al catálogo
                    </a>

                    <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                        <a
                            href="{{ route('products.edit', $product) }}"
                            class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2.5 rounded-xl text-sm font-medium transition hover:bg-gray-300 dark:hover:bg-gray-600 text-center"
                        >
                            Editar producto
                        </a>
                        <button
                            onclick="window.print()"
                            type="button"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-lg shadow-blue-600/30 text-center"
                        >
                            Imprimir etiqueta
                            </button>
                            <a
                                 href="{{ route('inventory.movements.index', $product) }}"
                                     class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition text-center"
                            >
                              Gestionar existencias
                             </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Estilos específicos para impresión -->
    @push('styles')
    <style>
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .label { border: none !important; box-shadow: none !important; margin: 0 auto; }
        }
    </style>
    @endpush
</x-app-layout>
