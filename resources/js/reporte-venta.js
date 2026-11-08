

        document.addEventListener('alpine:init', () => {
            const money = v => new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(v) || 0);

            // Número que sube de 0 al valor final (respeta "reducir movimiento")
            Alpine.data('countUp', (target, mode = 'int') => ({
                text: '',
                init() {
                    const fmt = v => mode === 'money'
                        ? money(v)
                        : new Intl.NumberFormat('es-MX').format(Math.round(v));

                    this.text = fmt(target);

                    if (!target || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                    const start = performance.now();
                    const duration = 800;
                    const step = now => {
                        const t = Math.min(1, (now - start) / duration);
                        this.text = fmt(target * (1 - Math.pow(1 - t, 3)));
                        if (t < 1) requestAnimationFrame(step);
                    };
                    requestAnimationFrame(step);
                },
            }));

            // Barras por día con alternancia Importe / Ventas
            Alpine.data('salesChart', (days) => ({
                days,
                metric: 'amount',
                active: 0,
                init() {
                    let best = 0;
                    this.days.forEach((d, i) => { if (d.amount > this.days[best].amount) best = i; });
                    this.active = best;
                },
                get current() {
                    return this.days[this.active] ?? { full: '—', amount: 0, count: 0 };
                },
                get max() {
                    return Math.max(1, ...this.days.map(d => d[this.metric]));
                },
                height(d) {
                    return Math.max(3, (d[this.metric] / this.max) * 100);
                },
                showLabel(i) {
                    const n = this.days.length;
                    const step = n > 14 ? Math.ceil(n / 8) : 1;
                    return i % step === 0 || i === n - 1;
                },
                money,
            }));

            // Dona de métodos de pago
            Alpine.data('paymentDonut', (items, total) => ({
                items,
                total,
                hover: null,
                money,
            }));

            // Buscador del detalle (filtra las ventas de la página actual)
            Alpine.data('salesList', (rows) => ({
                rows,
                q: '',
                show(i) {
                    const term = this.q.trim().toLowerCase();
                    return !term || (this.rows[i] ?? '').includes(term);
                },
                get matches() {
                    return this.rows.filter((_, i) => this.show(i)).length;
                },
            }));
        });
  