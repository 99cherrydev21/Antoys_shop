@php
    $lines = $sale->items->values()->map(function ($item, $i) use ($stocks) {
        return [
            'id' => $item->id,
            'name' => $item->product_name,
            'sku' => $item->product_sku,
            'original_price' => $item->original_price !== null ? (float) $item->original_price : null,
            'quantity' => (int) old("items.$i.quantity", $item->quantity),
            'unit_price' => (float) old("items.$i.unit_price", $item->unit_price),
            // Lo que se puede llegar a vender: existencias actuales + lo que ya tiene esta venta
            'max' => (int) ($stocks[$item->product_id] ?? 0) + (int) $item->quantity,
        ];
    });

    $receivedDefault = old('amount_received', $sale->payment_method === 'efectivo' ? $sale->amount_received : '');
@endphp

<x-app-layout>
    <div class="relative min-h-screen bg-gray-100 py-6 transition-colors duration-300 dark:bg-gray-900 sm:py-8"
         x-data="saleEdit(@js($lines), @js(old('payment_method', $sale->payment_method)), @js($receivedDefault))">
        <x-background-glow />

        <div class="relative z-10 mx-auto max-w-3xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Encabezado --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('sales.history') }}" title="Volver" aria-label="Volver"
                   class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-gray-300
                          bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2
                          focus:ring-teal-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Editar venta</h1>
                    <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                        {{ $sale->folio }} · {{ $sale->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>

            @if ($errors->any())
                <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-700 dark:border-red-700 dark:bg-red-900 dark:text-red-200">
                    <p class="font-semibold">No se pudo guardar:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('sales.update', $sale) }}" class="space-y-5"
                  @submit="if (submitting) { $event.preventDefault(); return; } submitting = true">
                @csrf
                @method('PUT')

                {{-- Productos --}}
                <div class="space-y-2">
                    <template x-for="(line, index) in lines" :key="line.id">
                        <div class="rounded-2xl border border-gray-200 bg-white p-3 shadow dark:border-gray-700 dark:bg-gray-800">
                            <input type="hidden" :name="`items[${index}][id]`" :value="line.id">

                            <div class="min-w-0">
                                <p class="line-clamp-2 text-sm font-bold leading-tight text-gray-800 dark:text-white" x-text="line.name"></p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    <span x-text="line.sku"></span>
                                    <template x-if="line.original_price !== null">
                                        <span> · Precio lista <span x-text="money(line.original_price)"></span></span>
                                    </template>
                                </p>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Precio c/u</label>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                        <input type="number" min="0" step="0.01" inputmode="decimal"
                                               :name="`items[${index}][unit_price]`"
                                               x-model.number="line.unit_price"
                                               @change="fixPrice(line)"
                                               class="w-full rounded-xl border border-gray-200 bg-white py-2 pl-6 pr-2 text-right font-bold
                                                      tabular-nums text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                                      dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Cantidad <span class="font-normal text-gray-400">(máx. <span x-text="line.max"></span>)</span>
                                    </label>
                                    <input type="number" min="1" :max="line.max" inputmode="numeric"
                                           :name="`items[${index}][quantity]`"
                                           x-model.number="line.quantity"
                                           @change="fixQuantity(line)"
                                           class="w-full rounded-xl border border-gray-200 bg-white px-2 py-2 text-center font-bold
                                                  tabular-nums text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                                  dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                </div>
                            </div>

                            <div class="mt-2 flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                                <span class="font-extrabold tabular-nums text-gray-800 dark:text-white"
                                      x-text="money((Number(line.unit_price) || 0) * (Number(line.quantity) || 0))"></span>
                            </div>
                        </div>
                    </template>

                    <p class="px-1 text-xs text-gray-500 dark:text-gray-400">
                        Si cambias una cantidad, el inventario se ajusta solo. Para quitar un producto de la venta,
                        elimina la venta y regístrala de nuevo.
                    </p>
                </div>

                {{-- Pago --}}
                <div class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-800">

                    <div class="flex items-end justify-between">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Total</span>
                        <span class="text-3xl font-extrabold tabular-nums text-teal-600 dark:text-teal-400" x-text="money(total)"></span>
                    </div>

                    <div>
                        <label for="payment_method" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Método de pago</label>
                        <select id="payment_method" name="payment_method" x-model="paymentMethod"
                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800
                                       focus:outline-none focus:ring-2 focus:ring-teal-500
                                       dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>

                    <div x-show="paymentMethod === 'efectivo'" x-cloak>
                        <label for="amount_received" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ¿Con cuánto pagó? <span class="font-normal text-gray-400">(opcional)</span>
                        </label>
                        <input id="amount_received" type="number" name="amount_received" x-model="amountReceived"
                               :disabled="paymentMethod !== 'efectivo'"
                               min="0" step="0.01" inputmode="decimal" placeholder="0.00"
                               class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-lg font-bold tabular-nums
                                      text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                      dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <p x-show="amountReceived !== '' && Number(amountReceived) >= total" x-cloak
                           class="mt-2 text-sm font-semibold text-green-700 dark:text-green-300">
                            Cambio: <span class="tabular-nums" x-text="money(change)"></span>
                        </p>
                        <p x-show="amountReceived !== '' && Number(amountReceived) < total" x-cloak
                           class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400">
                            El efectivo es menor que el total.
                        </p>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Notas (opcional)</label>
                        <textarea id="notes" name="notes" rows="2" maxlength="2000"
                                  class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800
                                         focus:outline-none focus:ring-2 focus:ring-teal-500
                                         dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('notes', $sale->notes) }}</textarea>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <a href="{{ route('sales.history') }}"
                       class="flex items-center justify-center rounded-xl border border-gray-200 px-4 py-3 font-semibold
                              text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancelar
                    </a>

                    <button type="submit" :disabled="submitting"
                            class="col-span-2 rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-3 font-semibold text-white
                                   shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-teal-600
                                   focus:outline-none focus:ring-2 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="submitting ? 'Guardando...' : 'Guardar cambios'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('saleEdit', (lines, method, received) => ({
                lines: lines ?? [],
                paymentMethod: method || 'efectivo',
                amountReceived: received ?? '',
                submitting: false,

                get total() {
                    const raw = this.lines.reduce(
                        (s, l) => s + (Number(l.unit_price) || 0) * (Number(l.quantity) || 0), 0
                    );
                    return Math.round((raw + Number.EPSILON) * 100) / 100;
                },
                get change() {
                    return Math.max(0, Math.round((Number(this.amountReceived || 0) - this.total) * 100) / 100);
                },
                money(v) {
                    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v) || 0);
                },
                fixPrice(line) {
                    const p = Number(line.unit_price);
                    line.unit_price = (line.unit_price === '' || line.unit_price == null || !Number.isFinite(p) || p < 0)
                        ? 0
                        : Math.round((p + Number.EPSILON) * 100) / 100;
                },
                fixQuantity(line) {
                    let q = Number.parseInt(line.quantity, 10);
                    if (!Number.isFinite(q) || q < 1) q = 1;
                    line.quantity = Math.min(q, Number(line.max));
                },
            }));
        });
    </script>
</x-app-layout>
