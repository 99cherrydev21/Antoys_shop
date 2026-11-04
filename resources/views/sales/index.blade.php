@php
    // Misma pantalla para vender y para editar: si llega $sale, estamos editando.
    $editing = !empty($sale);

    $initialItems = old('items', $editing
        ? $sale->items
            ->filter(fn ($item) => $item->product_id)
            ->map(fn ($item) => [
                'product_id' => (int) $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])
            ->values()
            ->all()
        : []);

    $initialPayment = old('payment_method', $editing ? $sale->payment_method : 'efectivo');
    $initialReceived = old('amount_received', '');
@endphp

<x-app-layout>
    <x-scanner.page
        :title="$editing ? 'Editar venta' : 'Punto de venta'"
        :subtitle="$editing ? $sale->folio . ' · agrega, quita o cambia productos y guarda.' : 'Escanea o busca los productos y toca Cobrar.'"
        :back="$editing ? route('sales.show', $sale) : route('sales.history')"
        :action="$editing ? route('sales.update', $sale) : route('sales.store')"
        lookup="#"
        alpine="salePos"
        :reason="false"
        placeholder="Número, SKU o nombre">

        @if ($editing)
            @method('PUT')
        @endif

        {{-- Popup para cambiar el precio (solo afecta esta venta) --}}
        <div x-show="priceOpen" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
             role="dialog" aria-modal="true" aria-labelledby="price-title"
             @keydown.escape.window="closePrice()">

            <div class="absolute inset-0 bg-gray-900/60" x-show="priceOpen" x-transition.opacity
                 @click="closePrice()"></div>

            <div class="relative w-full max-w-sm rounded-t-3xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:rounded-2xl"
                 x-show="priceOpen" x-transition>

                <h2 id="price-title" class="text-lg font-semibold text-gray-800 dark:text-white">Cambiar precio</h2>
                <p class="mt-0.5 line-clamp-2 text-sm text-gray-600 dark:text-gray-300"
                   x-text="priceItem ? priceItem.name : ''"></p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Solo cambia esta venta, no el catálogo. Precio de catálogo:
                    <span class="font-semibold tabular-nums" x-text="priceItem ? money(priceItem.base_price) : ''"></span>
                </p>

                <div class="relative mt-4">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xl text-gray-400">$</span>
                    <input x-ref="priceInput" type="number" x-model.number="priceDraft"
                           min="0" step="0.01" inputmode="decimal" aria-label="Nuevo precio"
                           @keydown.enter.prevent="savePrice()"
                           class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-9 pr-4 text-2xl font-extrabold
                                  tabular-nums text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                  dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>

                <p x-show="priceError" x-cloak x-text="priceError"
                   class="mt-2 text-sm font-medium text-red-600 dark:text-red-400"></p>

                <button type="button" x-show="priceItem && priceChanged(priceItem)" x-cloak
                        @click="restorePrice()"
                        class="mt-3 text-sm font-semibold text-teal-600 hover:underline dark:text-teal-400">
                    Volver al precio de catálogo
                </button>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <button type="button" @click="closePrice()"
                            class="rounded-xl border border-gray-200 px-4 py-3 font-semibold text-gray-700 transition
                                   hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="button" @click="savePrice()"
                            class="col-span-2 rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-3 font-semibold
                                   text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-teal-600
                                   focus:outline-none focus:ring-2 focus:ring-teal-400">
                        Guardar precio
                    </button>
                </div>
            </div>
        </div>

        {{-- Popup de cobro / guardado (dentro del form para que sus campos se envíen) --}}
        <div x-show="paymentOpen" x-cloak
             class="fixed inset-0 z-50 flex items-end justify-center sm:items-center"
             role="dialog" aria-modal="true" aria-labelledby="payment-title"
             @keydown.escape.window="closePayment()">

            <div class="absolute inset-0 bg-gray-900/60" x-show="paymentOpen" x-transition.opacity
                 @click="closePayment()"></div>

            <div class="relative max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-3xl bg-white p-5 shadow-2xl dark:bg-gray-800 sm:rounded-2xl"
                 x-show="paymentOpen" x-transition>

                <h2 id="payment-title" class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $editing ? 'Nuevo total de la venta' : 'Total a cobrar' }}
                </h2>
                <p class="text-4xl font-extrabold tabular-nums text-teal-600 dark:text-teal-400" x-text="money(total)"></p>

                @if ($editing)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Al guardar, el inventario se ajusta con los productos agregados, quitados o cambiados.
                    </p>
                @endif

                {{-- Método de pago --}}
                <div class="mt-5 grid grid-cols-3 gap-2">
                    <template x-for="m in methods" :key="m.value">
                        <label class="cursor-pointer rounded-xl border-2 px-2 py-4 text-center text-sm font-semibold transition focus-within:ring-2 focus-within:ring-teal-500"
                               :class="paymentMethod === m.value
                                   ? 'border-teal-500 bg-teal-50 text-teal-800 dark:bg-teal-900 dark:text-teal-100'
                                   : 'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700'">
                            <input type="radio" name="payment_method" :value="m.value" x-model="paymentMethod" class="sr-only">
                            <span x-text="m.label"></span>
                        </label>
                    </template>
                </div>

                {{-- Efectivo: opcional, solo para calcular el cambio --}}
                <div x-show="paymentMethod === 'efectivo'" x-cloak class="mt-4">
                    <label for="amount_received" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        ¿Con cuánto paga? <span class="font-normal text-gray-400">(opcional)</span>
                    </label>
                    <input id="amount_received" type="number" name="amount_received" x-model="amountReceived"
                           :disabled="paymentMethod !== 'efectivo'"
                           min="0" step="0.01" inputmode="decimal" placeholder="0.00"
                           class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-lg font-bold tabular-nums
                                  text-gray-800 focus:outline-none focus:ring-2 focus:ring-teal-500
                                  dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <p x-show="amountReceived !== '' && Number(amountReceived) >= total" x-cloak
                       class="mt-2 text-sm font-semibold text-green-700 dark:text-green-300">
                        Cambio: <span class="tabular-nums" x-text="money(change)"></span>
                    </p>
                </div>

                {{-- Tarjeta / transferencia: foto opcional --}}
                <div x-show="paymentMethod !== 'efectivo'" x-cloak class="mt-4">
                    @if ($editing && $sale->receipt_path)
                        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">
                            Esta venta ya tiene comprobante. Si subes otra foto, la reemplaza.
                        </p>
                    @endif

                    <input id="receipt" x-ref="receipt" type="file" name="receipt" accept="image/*"
                           :disabled="paymentMethod === 'efectivo'" @change="onReceipt($event)"
                           class="peer sr-only">

                    <label for="receipt" x-show="!receiptUrl"
                           class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border-2 border-dashed
                                  border-blue-200 bg-blue-50/50 px-4 py-4 text-sm font-medium text-blue-700 transition
                                  hover:border-teal-400 peer-focus-visible:ring-2 peer-focus-visible:ring-teal-500
                                  dark:border-gray-600 dark:bg-slate-900 dark:text-blue-400">
                        Foto del comprobante (opcional)
                    </label>

                    <div x-show="receiptUrl" x-cloak class="flex items-center gap-3 rounded-xl border border-gray-200 p-2 dark:border-gray-700">
                        <img :src="receiptUrl" alt="Comprobante" class="h-16 w-16 shrink-0 rounded-lg object-cover">
                        <span class="flex-1 text-sm text-gray-600 dark:text-gray-300">Comprobante listo</span>
                        <button type="button" @click="clearReceipt()"
                                class="rounded-lg px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-700">
                            Quitar
                        </button>
                    </div>
                </div>

                {{-- Notas: al editar se muestran para conservarlas --}}
                @if ($editing)
                    <div class="mt-4">
                        <label for="sale-notes" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Notas <span class="font-normal text-gray-400">(opcional)</span>
                        </label>
                        <textarea id="sale-notes" name="notes" rows="2" maxlength="2000"
                                  class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-800
                                         focus:outline-none focus:ring-2 focus:ring-teal-500
                                         dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ old('notes', $sale->notes) }}</textarea>
                    </div>
                @endif

                <p x-show="paymentError" x-cloak x-text="paymentError"
                   class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-900 dark:text-red-200"></p>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <button type="button" @click="closePayment()"
                            class="rounded-xl border border-gray-200 px-4 py-3 font-semibold text-gray-700 transition
                                   hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                        Cancelar
                    </button>
                    <button type="button" @click="confirmPayment($el)" :disabled="submitting"
                            class="col-span-2 rounded-xl bg-gradient-to-r from-blue-500 to-teal-500 px-4 py-3 font-semibold
                                   text-white shadow-lg transition-all duration-300 hover:from-blue-600 hover:to-teal-600
                                   focus:outline-none focus:ring-2 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-50">
                        {{ $editing ? 'Guardar cambios' : 'Confirmar venta' }}
                    </button>
                </div>
            </div>
        </div>
    </x-scanner.page>

    <script>
        const POS = {
            editing: @js($editing),
            products: @js($products),
            items: @js($initialItems),
            payment: @js($initialPayment),
            received: @js($initialReceived),
        };

        document.addEventListener('alpine:init', () => {
            Alpine.data('salePos', () => ({
                // Estado que usan los componentes x-scanner
                products: POS.products ?? [],
                items: [], filter: '', scanning: false, manual: false, manualSku: '',
                message: '', messageError: false, submitting: false, submitError: '',
                minQuantity: 1, showStock: true, stockLabel: 'Existencias', _msgTimer: null,

                // Cobro
                editing: POS.editing,
                confirmed: false, paymentOpen: false,
                paymentMethod: POS.payment || 'efectivo',
                amountReceived: POS.received ?? '',
                paymentError: '', receiptUrl: '',
                methods: [
                    { value: 'efectivo', label: 'Efectivo' },
                    { value: 'tarjeta', label: 'Tarjeta' },
                    { value: 'transferencia', label: 'Transferencia' },
                ],

                init() {
                    // Productos iniciales: los de la venta (al editar) o los de un intento fallido (old).
                    this.items = (Array.isArray(POS.items) ? POS.items : []).map(old => {
                        const p = this.products.find(p => Number(p.id) === Number(old.product_id));
                        if (!p) return null;
                        const sold = old.unit_price !== undefined && old.unit_price !== '' ? Number(old.unit_price) : Number(p.price);
                        return {
                            ...p,
                            base_price: Number(p.price),
                            price: Number.isFinite(sold) && sold >= 0 ? sold : Number(p.price),
                            quantity: Math.min(Math.max(1, Number(old.quantity) || 1), Number(p.stock)),
                            flash: false,
                        };
                    }).filter(Boolean);

                    window.addEventListener('pageshow', e => {
                        if (e.persisted) { this.submitting = false; this.confirmed = false; }
                    });
                },

                // Totales
                get filteredItems() {
                    const t = this.filter.trim().toLowerCase();
                    if (!t) return this.items;
                    return this.items.filter(i => String(i.name).toLowerCase().includes(t)
                        || String(i.sku).toLowerCase().includes(t) || String(i.id) === t);
                },
                get totalUnits() { return this.items.reduce((s, i) => s + (Number(i.quantity) || 0), 0); },
                get total() {
                    const raw = this.items.reduce((s, i) => s + Number(i.price) * (Number(i.quantity) || 0), 0);
                    return Math.round((raw + Number.EPSILON) * 100) / 100;
                },
                get change() { return Math.max(0, Math.round((Number(this.amountReceived || 0) - this.total) * 100) / 100); },
                get submitText() {
                    if (this.submitting) return this.editing ? 'Guardando cambios...' : 'Registrando venta...';
                    const label = this.editing ? 'Guardar cambios' : 'Cobrar';
                    return this.items.length ? label + ' · ' + this.money(this.total) : label;
                },
                money(v) {
                    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v) || 0);
                },

                // Escaneo y búsqueda
                exactMatch(term) {
                    const t = String(term ?? '').trim().toLowerCase();
                    return t ? (this.products.find(p => String(p.sku).toLowerCase() === t || String(p.id) === t) ?? null) : null;
                },
                search(term) {
                    const t = String(term ?? '').trim().toLowerCase();
                    return t ? this.products.filter(p => String(p.name).toLowerCase().includes(t)
                        || String(p.sku).toLowerCase().includes(t)) : [];
                },
                get manualResults() { return this.manualSku.trim().length >= 2 ? this.search(this.manualSku).slice(0, 6) : []; },
                scan(code) {
                    // La zona tiene su propio x-data, así que su input no aparece en this.$refs
                    const input = this.$root.querySelector('[x-ref="scanner"]');
                    if (input) input.value = '';

                    const v = String(code ?? '').trim();
                    if (!v || this.paymentOpen || this.priceOpen) return;
                    const p = this.exactMatch(v);
                    p ? this.addProduct(p) : this.say('No se encontró el producto: ' + v, true);
                },
                addManual() {
                    const term = this.manualSku.trim();
                    if (!term) return;
                    const found = this.exactMatch(term) ? [this.exactMatch(term)] : this.search(term);
                    if (found.length === 0) return this.say('No se encontró: ' + term, true);
                    if (found.length > 1) return this.say(found.length + ' coincidencias, elige una de la lista.', true);
                    this.addProduct(found[0]);
                    this.manualSku = '';
                },
                pickManual(p) { this.addProduct(p); this.manualSku = ''; },
                say(text, error = false) {
                    this.message = text; this.messageError = error;
                    clearTimeout(this._msgTimer);
                    this._msgTimer = setTimeout(() => this.message = '', 2500);
                },

                // Carrito
                addProduct(p) {
                    const stock = Number(p.stock);
                    if (stock < 1) return this.say('Sin existencias: ' + p.name, true);
                    const existing = this.items.find(i => Number(i.id) === Number(p.id));
                    if (existing) {
                        if (Number(existing.quantity) >= stock) {
                            this.flashItem(existing);
                            return this.say('Ya agregaste todas las existencias de ' + p.name, true);
                        }
                        existing.quantity = Number(existing.quantity) + 1;
                    } else {
                        this.items.unshift({ ...p, base_price: Number(p.price), quantity: 1, flash: false });
                    }
                    this.say(p.name + ' agregado');
                },
                remove(id) { this.items = this.items.filter(i => Number(i.id) !== Number(id)); },
                clearItems() { if (confirm('¿Deseas vaciar la lista de productos?')) this.items = []; },
                increase(item) {
                    if (Number(item.quantity) >= Number(item.stock)) return this.flashItem(item);
                    item.quantity = Number(item.quantity) + 1;
                },
                decrease(item) { if (Number(item.quantity) > this.minQuantity) item.quantity = Number(item.quantity) - 1; },
                validateQuantity(item, final = false) {
                    let q = Number.parseInt(item.quantity, 10);
                    if (!Number.isFinite(q)) { if (final) item.quantity = this.minQuantity; return; }
                    if (q > Number(item.stock)) { q = Number(item.stock); this.flashItem(item); }
                    if (final && q < this.minQuantity) q = this.minQuantity;
                    item.quantity = q;
                },
                exceeds(item) { return Number(item.quantity) > Number(item.stock); },
                warnText(item) {
                    if (this.exceeds(item)) return 'Máximo disponible: ' + item.stock;
                    return Number(item.quantity) >= Number(item.stock) ? 'Alcanzaste las existencias disponibles.' : '';
                },
                flashItem(item) { item.flash = true; setTimeout(() => item.flash = false, 600); },

                // Precio editable (solo afecta esta venta, nunca el catálogo)
                // El precio se cambia desde un popup al tocar el precio de la tarjeta
                priceOpen: false, priceItem: null, priceDraft: '', priceError: '',
                priceChanged(item) { return Number(item.price) !== Number(item.base_price); },
                openPrice(item) {
                    document.activeElement?.blur?.();
                    this.priceItem = item;
                    this.priceDraft = item.price;
                    this.priceError = '';
                    this.priceOpen = true;
                    setTimeout(() => {
                        this.$refs.priceInput?.focus();
                        this.$refs.priceInput?.select();
                    }, 60);
                },
                closePrice() { this.priceOpen = false; this.priceItem = null; },
                savePrice() {
                    const raw = this.priceDraft;
                    const p = Number(raw);
                    if (raw === '' || raw == null || !Number.isFinite(p) || p < 0) {
                        this.priceError = 'Escribe un precio válido (0 o mayor).';
                        return;
                    }
                    this.priceItem.price = Math.round((p + Number.EPSILON) * 100) / 100;
                    this.closePrice();
                },
                restorePrice() {
                    if (this.priceItem) this.priceItem.price = this.priceItem.base_price;
                    this.closePrice();
                },

                // Cobro: el botón de abajo abre el popup; el botón del popup envía el form
                validateBeforeSubmit(event) {
                    if (this.submitting) { event.preventDefault(); return; }
                    if (!this.confirmed) {
                        event.preventDefault();
                        if (this.items.length) {
                            document.activeElement?.blur?.();
                            this.paymentError = '';
                            this.paymentOpen = true;
                        }
                        return;
                    }
                    this.submitting = true;
                },
                closePayment() { if (!this.submitting) this.paymentOpen = false; },
                confirmPayment(button) {
                    if (this.paymentMethod === 'efectivo' && this.amountReceived !== '' && Number(this.amountReceived) < this.total) {
                        this.paymentError = 'El efectivo es menor que el total.';
                        return;
                    }
                    this.confirmed = true;
                    button.closest('form').requestSubmit();
                },
                onReceipt(event) {
                    const file = event.target.files[0];
                    if (!file) return this.clearReceipt();
                    if (file.size > 5 * 1024 * 1024) { this.clearReceipt(); this.paymentError = 'La foto no debe pesar más de 5 MB.'; return; }
                    this.paymentError = '';
                    if (this.receiptUrl) URL.revokeObjectURL(this.receiptUrl);
                    this.receiptUrl = URL.createObjectURL(file);
                },
                clearReceipt() {
                    if (this.receiptUrl) URL.revokeObjectURL(this.receiptUrl);
                    this.receiptUrl = '';
                    if (this.$refs.receipt) this.$refs.receipt.value = '';
                },
            }));
        });
    </script>
</x-app-layout>
