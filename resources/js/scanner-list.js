/**
 * Une un componente base con extensiones conservando los getters.
 * Si ambos definen init(), corren los dos (primero el base).
 */
export function extend(base, extra) {
    const merged = Object.defineProperties({}, Object.getOwnPropertyDescriptors(base));

    for (const [key, desc] of Object.entries(Object.getOwnPropertyDescriptors(extra))) {
        if (key === 'init' && typeof base.init === 'function') {
            const baseInit = base.init;
            const extraInit = desc.value;

            Object.defineProperty(merged, 'init', {
                value() {
                    baseInit.call(this);
                    extraInit.call(this);
                },
                writable: true,
                configurable: true,
                enumerable: true,
            });
        } else {
            Object.defineProperty(merged, key, desc);
        }
    }

    return merged;
}

/** Deslizar a la izquierda para quitar (misma mecánica del catálogo). */
export function swipeRow() {
    return {
        W: 96,
        startX: 0,
        startY: 0,
        startOffset: 0,
        offset: 0,
        tracking: false,
        moved: false,

        start(e) {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (e.target.closest('input, textarea')) return; // deja seleccionar texto
            this.startX = e.clientX;
            this.startY = e.clientY;
            this.startOffset = this.offset;
            this.tracking = true;
            this.moved = false;
        },

        move(e) {
            if (!this.tracking) return;
            const dx = e.clientX - this.startX;
            const dy = e.clientY - this.startY;

            if (Math.abs(dx) > 10 || Math.abs(dy) > 10) this.moved = true;

            if (Math.abs(dx) > Math.abs(dy)) {
                this.offset = Math.max(-this.W, Math.min(0, this.startOffset + dx));
            }
        },

        end() {
            if (!this.tracking) return;
            this.offset = this.offset < -this.W / 2 ? -this.W : 0;
            this.tracking = false;
            setTimeout(() => (this.moved = false), 0);
        },

        cancel() {
            this.tracking = false;
            this.offset = this.startOffset < 0 ? -this.W : 0;
        },
    };
}

/**
 * Escáner + lista de productos.
 * config: { lookupUrl, lookupParam, type, limitTypes, labels }
 */
export default function scannerList(config = {}) {
    return {
        // ---- configuración (viene del componente Blade) ----
        lookupUrl: config.lookupUrl ?? '',
        lookupParam: config.lookupParam ?? 'sku',
        limitTypes: config.limitTypes ?? ['salida'],
        labels: config.labels ?? { default: 'Registrar' },
        type: config.type ?? null,
        minQuantity: config.minQuantity ?? 1,
        showStock: config.showStock ?? true,
        stockLabel: config.stockLabel ?? 'Stock actual',

        // ---- estado ----
        items: (config.initialItems ?? []).map((i) => ({ warn: '', flash: false, ...i })),
        filter: '',
        scanning: false,
        manual: false,
        manualSku: '',
        message: '',
        messageError: false,
        submitError: '',
        submitting: false,
        timers: {},
        msgTimer: null,

        init() {
            this.$watch('type', () => (this.submitError = ''));
            this.$watch('totalUnits', () => (this.submitError = ''));

            // En computadora (mouse) deja el lector listo al cargar
            if (window.matchMedia('(pointer: fine)').matches) {
                this.$nextTick(() => this.$refs.scanner?.focus());
            }
        },

        // ---- getters ----
        get totalUnits() {
            return this.items.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
        },

        get filteredItems() {
            const term = this.filter.trim().toLowerCase();
            if (!term) return this.items;

            // Si es un número, buscamos con relleno de 7 ceros (ANT-0000001)
            const isNumeric = /^\d+$/.test(term);
            const paddedTerm = isNumeric ? term.padStart(7, '0') : null;

            return this.items.filter((item) => {
                const nameMatch = item.name.toLowerCase().includes(term);

                let skuMatch = item.sku.toLowerCase().includes(term);
                if (isNumeric) {
                    skuMatch = skuMatch || item.sku.includes(paddedTerm);
                }

                return nameMatch || skuMatch;
            });
        },

        get submitText() {
            if (this.submitting) return 'Registrando operación...';

            const label = this.labels[this.type] ?? this.labels.default ?? 'Registrar';
            const n = this.totalUnits;

            return `${label} · ${n} ${n === 1 ? 'unidad' : 'unidades'}`;
        },

        // ---- ganchos ----
        sanitize(raw) {
            let code = String(raw || '').trim().toUpperCase().replace(/'/g, '-');

            // Si el usuario solo escribió un número (ej. "1"), lo formateamos a "ANT-0000001"
            if (/^\d+$/.test(code)) {
                code = 'ANT-' + code.padStart(7, '0');
            }

            return code;
        },

        normalize(product) {
            return { ...product, quantity: 1, warn: '', flash: false };
        },

        // ---- límites de stock ----
        limited() {
            return this.limitTypes.includes('*') || this.limitTypes.includes(this.type);
        },

        exceeds(item) {
            return this.limited() && item.quantity > item.stock;
        },

        warnText(item) {
            if (item.warn) return item.warn;
            if (this.exceeds(item)) {
                return `La cantidad supera el stock disponible (${item.stock}).`;
            }
            return '';
        },

        flashItem(item, text) {
            item.warn = text;
            item.flash = true;
            clearTimeout(this.timers[item.id]);
            this.timers[item.id] = setTimeout(() => {
                item.warn = '';
                item.flash = false;
            }, 2500);
        },

        clearWarn(item) {
            clearTimeout(this.timers[item.id]);
            item.warn = '';
            item.flash = false;
        },

        notify(text, error = false) {
            this.message = text;
            this.messageError = error;
            clearTimeout(this.msgTimer);
            this.msgTimer = setTimeout(() => (this.message = ''), 3500);
        },

        // ---- escaneo ----
        addManual() {
            const value = this.manualSku;
            this.manualSku = '';
            this.scan(value, 'manual');
        },

        async scan(raw, source = 'scanner') {
            const sku = this.sanitize(raw);
            if (this.$refs.scanner) this.$refs.scanner.value = '';

            if (!sku) return;

            try {
                const url = new URL(this.lookupUrl, window.location.origin);
                url.searchParams.set(this.lookupParam, sku);

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                const product = await response.json();

                if (!response.ok) {
                    throw new Error(product.message || 'No se encontró el producto.');
                }

                const index = this.items.findIndex((item) => item.id === product.id);

                if (index !== -1) {
                    const existing = this.items[index];
                    const ok = this.increase(existing);

                    this.items.splice(index, 1);
                    this.items.unshift(existing);

                    if (!ok) {
                        this.notify(`No hay más existencias de ${existing.name}.`, true);
                        return;
                    }
                } else {
                    if (this.limited() && Number(product.stock) < 1) {
                        this.notify(`${product.name} no tiene existencias.`, true);
                        return;
                    }

                    this.items.unshift(this.normalize(product));
                }

                this.notify(`${product.name} agregado.`);
            } catch (error) {
                this.notify(error.message || 'No se pudo buscar el producto.', true);
            } finally {
                (source === 'manual' ? this.$refs.manualInput : this.$refs.scanner)?.focus();
            }
        },

        // ---- cantidades ----
        increase(item) {
            if (this.limited() && item.quantity >= item.stock) {
                this.flashItem(item, `Solo hay ${item.stock} en existencia: no puedes sacar más.`);
                return false;
            }

            item.quantity = (Number(item.quantity) || 0) + 1;
            this.clearWarn(item);
            return true;
        },

        decrease(item) {
            const min = this.minQuantity;

            if ((Number(item.quantity) || 0) <= min) {
                this.flashItem(item, `Ya no puedes disminuir: se alcanzó el mínimo (${min}).`);
                return;
            }

            item.quantity--;
            this.clearWarn(item);
        },

        validateQuantity(item, final = false) {
            const min = this.minQuantity;

            const belowMin = () => {
                if (!final) return;
                item.quantity = min;
                this.flashItem(item, `Ya no puedes disminuir: se alcanzó el mínimo (${min}).`);
            };

            if (item.quantity === '' || item.quantity === null) return belowMin();

            let qty = Math.floor(Number(item.quantity));
            if (!Number.isFinite(qty)) qty = min;
            if (qty < min) return belowMin();

            if (this.limited() && qty > item.stock) {
                item.quantity = Math.max(min, Number(item.stock));
                this.flashItem(item, `Solo hay ${item.stock} en existencia: no puedes sacar más.`);
                return;
            }

            item.quantity = qty;
        },

        remove(id) {
            clearTimeout(this.timers[id]);
            this.items = this.items.filter((item) => item.id !== id);
        },

        clearItems() {
            if (this.items.length === 0) return;
            if (!confirm('¿Quitar todos los productos de la lista?')) return;

            this.items = [];
            this.filter = '';
            this.message = '';
            this.$refs.scanner?.focus();
        },

        // ---- envío ----
        validateBeforeSubmit(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }

            if (this.items.length === 0) {
                event.preventDefault();
                this.submitError = 'Escanea al menos un producto antes de registrar.';
                return;
            }

            const invalid = this.items.filter((item) => {
                const q = Number(item.quantity);
                return !Number.isInteger(q) || q < this.minQuantity;
            });

            if (invalid.length) {
                event.preventDefault();
                this.submitError = `Hay cantidades inválidas. La cantidad mínima es ${this.minQuantity}.`;
                return;
            }

            const over = this.items.filter((item) => this.exceeds(item));

            if (over.length) {
                event.preventDefault();
                this.submitError = `Hay ${over.length} producto(s) con cantidad mayor al stock disponible. Corrige las cantidades en rojo.`;
                return;
            }

            this.submitting = true;
        },
    };
}
