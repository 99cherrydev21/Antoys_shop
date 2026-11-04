<x-app-layout>
    <div class="min-h-screen bg-gray-100 py-8 dark:bg-gray-900">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm font-semibold text-green-800 dark:border-green-800 dark:bg-green-950/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Acciones: no se imprimen --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3 print:hidden">
    <!-- Botón Historial -->
    <a
        href="{{ route('sales.history') }}"
        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-750"
    >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Historial ventas
    </a>

    <!-- Grupo derecho: Nueva venta + Imprimir -->
    <div class="flex flex-wrap items-center gap-3">
        <!-- Botón Nueva Venta (Estilo secundario azul/teal) -->
        <a
            href="{{ route('sales.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/50 px-4 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-teal-50 hover:border-teal-300 hover:text-teal-800 dark:border-gray-700 dark:bg-slate-900 dark:text-blue-400 dark:hover:bg-gray-800"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva venta
        </a>

        <!-- Botón Imprimir Ticket (Estilo principal vibrante en tonos Teal/Azul) -->
        <button
            type="button"
            onclick="window.print()"
            class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-bold text-white shadow-md shadow-teal-600/20 transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 dark:bg-teal-600 dark:hover:bg-teal-500"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir ticket
        </button>
    </div>
</div>

            {{-- Ticket --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 text-gray-900 shadow-sm dark:border-gray-700 dark:bg-white sm:p-10 print:rounded-none print:border-0 print:p-0 print:shadow-none">

                <div class="border-b border-dashed border-gray-300 pb-6 text-center">
                    <h1 class="text-3xl font-black tracking-tight">
                        ANTOYS
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Comprobante de venta
                    </p>

                    <div class="mt-5 inline-flex rounded-full bg-green-100 px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-green-800">
                        Venta registrada
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 border-b border-dashed border-gray-300 py-5 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-gray-500">Folio</p>
                        <p class="mt-1 break-all font-bold">{{ $sale->folio }}</p>
                    </div>

                    <div>
                        <p class="text-gray-500">Fecha</p>
                        <p class="mt-1 font-semibold">
                            {{ $sale->created_at->format('d/m/Y H:i:s') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Atendió</p>
                        <p class="mt-1 font-semibold">
                            {{ $sale->user?->name ?? 'Usuario no disponible' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-gray-500">Método de pago</p>
                        <p class="mt-1 font-semibold">
                            {{ ucfirst($sale->payment_method) }}
                        </p>
                    </div>
                </div>

                <div class="py-5">
                    <h2 class="mb-4 text-sm font-bold uppercase tracking-wider text-gray-500">
                        Productos
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                                    <th class="pb-3 pr-3">Producto</th>
                                    <th class="pb-3 px-2 text-center">Cant.</th>
                                    <th class="pb-3 px-2 text-right">Precio</th>
                                    <th class="pb-3 pl-2 text-right">Importe</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100">
                                @foreach ($sale->items as $item)
                                    <tr>
                                        <td class="py-4 pr-3">
                                            <p class="font-bold">{{ $item->product_name }}</p>
                                            <p class="mt-1 text-xs text-gray-500">
                                                ID: {{ $item->product_sku }}
                                            </p>
                                        </td>

                                        <td class="px-2 py-4 text-center">
                                            {{ $item->quantity }}
                                        </td>

                                        <td class="whitespace-nowrap px-2 py-4 text-right">
                                            ${{ number_format((float) $item->unit_price, 2) }}
                                        </td>

                                        <td class="whitespace-nowrap py-4 pl-2 text-right font-semibold">
                                            ${{ number_format((float) $item->subtotal, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-3 border-t border-dashed border-gray-300 pt-5">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Subtotal</span>
                        <span class="font-semibold">
                            ${{ number_format((float) $sale->subtotal, 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-xl font-black">
                        <span>Total</span>
                        <span>${{ number_format((float) $sale->total, 2) }}</span>
                    </div>

                    @if ($sale->payment_method === 'efectivo')
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Efectivo recibido</span>
                            <span>
                                ${{ number_format((float) $sale->amount_received, 2) }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm font-bold">
                            <span>Cambio</span>
                            <span>
                                ${{ number_format((float) $sale->change_amount, 2) }}
                            </span>
                        </div>
                    @endif
                </div>

                @if ($sale->notes)
                    <div class="mt-6 border-t border-dashed border-gray-300 pt-5">
                        <p class="text-xs font-bold uppercase text-gray-500">
                            Notas
                        </p>
                        <p class="mt-2 whitespace-pre-line text-sm">{{ $sale->notes }}</p>
                    </div>
                @endif

                <div class="mt-8 border-t border-dashed border-gray-300 pt-6 text-center">
                    <p class="font-bold">¡Gracias por tu compra!</p>
                    <p class="mt-2 text-xs text-gray-500">
                        Conserva este comprobante para cualquier aclaración.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            @page {
                margin: 12mm;
            }

            body {
                background: white !important;
            }

            body * {
                visibility: hidden;
            }

            .min-h-screen,
            .min-h-screen * {
                visibility: visible;
            }

            .min-h-screen {
                position: absolute;
                inset: 0;
                width: 100%;
                min-height: auto !important;
                padding: 0 !important;
                background: white !important;
            }

            .print\:hidden {
                display: none !important;
            }

            .dark\:bg-white {
                background: white !important;
            }

            .shadow-sm {
                box-shadow: none !important;
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</x-app-layout>
