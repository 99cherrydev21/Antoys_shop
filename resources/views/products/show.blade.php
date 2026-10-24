<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative"
         x-data="{
             openPrintModal: false,
             quantity: 10,
             printLabels(count) {
                 this.quantity = Math.max(1, Math.min(50, count || 1));
                 this.openPrintModal = false;
                 this.$nextTick(() => window.print());
             }
         }">
        <x-background-glow />

        <div class="main-content-view relative z-10 max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-8 border border-white/20 dark:border-gray-700/50">

                <!-- Encabezado -->
                <div class="mb-8 border-b border-gray-200/80 dark:border-gray-700/80 pb-5 flex items-center gap-4">
                    <a href="{{ route('products.index') }}"
                       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                       title="Volver al catálogo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ $product->name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Detalles e impresión de etiqueta del producto.</p>
                    </div>
                </div>

                <!-- Info del producto -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
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

                <!-- Vista previa etiqueta individual -->
                <div class="flex justify-center mb-8">
                    <div class="bg-white text-gray-900 border border-gray-300 dark:border-gray-700 p-6 rounded-2xl w-72 text-center shadow-sm">
                        <h2 class="text-lg font-bold tracking-wider mb-1">Antoys</h2>
                        <p class="text-sm font-medium text-gray-700 mb-4 truncate">{{ $product->name }}</p>
                        <div class="flex justify-center mb-3">
                            <img src="data:image/png;base64,{{ $barcode }}"
                                 alt="Data Matrix del Id {{ $product->sku }}"
                                 class="w-40 h-40 object-contain image-rendering-pixelated">
                        </div>
                        <p class="text-sm font-mono font-bold tracking-widest text-gray-600">{{ $product->sku }}</p>
                    </div>
                </div>

                <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto justify-end">

                    <!-- Botón: Imprimir hoja de etiquetas (Gradiente Azul-Verde) -->
                    <button
                        @click="openPrintModal = true"
                        type="button"
                        class="w-full sm:w-auto bg-white dark:bg-gray-800 border-2 border-blue-500 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-center">
                        Imprimir hoja de etiquetas
                    </button>

                    <!-- Botón: Imprimir etiqueta única (Estilo secundario, borde azul) -->
                    <button
                        @click="printLabels(1)"
                        type="button"
                        class="w-full sm:w-auto bg-white dark:bg-gray-800 border-2 border-blue-500 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 text-center">
                        Imprimir etiqueta única
                    </button>

                </div>
            </div>

            </div>
        </div>


        <div
            x-show="openPrintModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
            x-transition.opacity>
            <div
                @click.away="openPrintModal = false"
                class="bg-white dark:bg-gray-800 rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 dark:border-gray-700">

                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">Imprimir Hoja de Etiquetas</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Indica cuántas etiquetas de tamaño aprox. 6×3 cm deseas acomodar en la hoja A4
                    (máx. ~24 por hoja, se paginará automáticamente).
                </p>

                <div class="mb-5">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-2">Cantidad de etiquetas</label>
                    <input
                        type="number"
                        x-model.number="quantity"
                        min="1"
                        max="50"
                        @input="quantity = Math.max(1, Math.min(50, quantity || 1))"
                        class="w-full bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-gray-800 dark:text-gray-200 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <p class="mt-2 text-xs text-gray-400">
                        Sugerido: hasta 24 etiquetas por hoja A4.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button
                        @click="openPrintModal = false"
                        type="button"
                        class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        Cancelar
                    </button>
                    <button
                        @click="printLabels(quantity)"
                        type="button"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition shadow-md">
                        Generar e Imprimir
                    </button>
                </div>
            </div>
        </div>


        <!-- HOJA DE ETIQUETAS MÚLTIPLES (solo visible al imprimir)       -->
        <div class="print-sheet">
            <div class="grid-labels">
                <template x-for="i in quantity" :key="i">
                    <div class="sheet-label">
                        <div class="label-header">
                            <span class="brand">Antoys</span>
                            <span class="sku-top">{{ $product->sku }}</span>
                        </div>
                        <div class="label-body">
                            <div class="info">
                                <h4 class="prod-name">{{ $product->name }}</h4>
                                <p class="prod-desc">{{ Str::limit($product->description ?? 'Sin descripción', 45) }}</p>
                                <span class="prod-price">${{ number_format($product->price, 2) }}</span>
                            </div>
                            <div class="qr-box">
                                <img src="data:image/png;base64,{{ $barcode }}" alt="DataMatrix">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

    </div>


    <!-- ESTILOS DE IMPRESIÓN                                         -->

 <style>
    /* Ocultar hoja de etiquetas en pantalla normal */
    .print-sheet { display: none; }

    /* Estilos exclusivos para impresión (A4: 21 x 29.7 cm, Etiquetas 6 x 3 cm) */
    @media print {
        @page {
            size: A4 portrait;
            margin: 0.8cm;
        }

        /* 1. TRUCO DEFINITIVO: Ocultar absolutamente todo el layout de la app */
        body * {
            visibility: hidden;
        }

        /* 2. Hacer visible ÚNICAMENTE la hoja de etiquetas y sus elementos internos */
        .print-sheet, .print-sheet * {
            visibility: visible;
        }

        /* 3. Posicionar la hoja arriba a la izquierda para que no deje espacios en blanco */
        .print-sheet {
            display: block !important;
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        /* 4. Limpiar fondos por completo para quitar los "destellos" de color */
        html, body {
            background: #fff !important;
            background-image: none !important;
            margin: 0 !important;
            padding: 0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* 5. Grid de 3 columnas de 6cm */
        .grid-labels {
            display: grid;
            grid-template-columns: repeat(3, 6cm);
            gap: 0.3cm;
            justify-content: center;
            align-content: start;
        }

        /* 6. Estilo de la etiqueta SIN bordes punteados y fondo blanco puro */
        .sheet-label {
            width: 6cm;
            height: 3cm;
            border: 1px dashed #999;
            border-radius: 4px;
            padding: 2mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .label-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 0.5px solid #333;
            padding-bottom: 1mm;
        }
        .label-header .brand {
            font-weight: 800;
            letter-spacing: 1px;
            font-size: 9pt;
        }
        .label-header .sku-top {
            font-family: monospace;
            font-size: 6.5pt;
            color: #555;
        }

        .label-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5mm;
            flex: 1;
            padding-top: 1mm;
            overflow: hidden;
        }

        .label-body .info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }
        .prod-name {
            font-size: 7.5pt;
            font-weight: 700;
            margin: 0;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .prod-desc {
            font-size: 5.5pt;
            color: #444;
            margin: 0.5mm 0;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .prod-price {
            font-size: 8pt;
            font-weight: 800;
            color: #000;
            margin-top: 0.5mm;
        }

        .qr-box {
            flex-shrink: 0;
            width: 18mm;
            height: 18mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            image-rendering: pixelated;
        }
    }
</style>
</x-app-layout>
