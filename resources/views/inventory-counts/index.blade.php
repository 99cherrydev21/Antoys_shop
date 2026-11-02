@php
    // Columnas compartidas entre el encabezado y cada fila (escritorio)
    $gridCols = 'md:grid-cols-[1fr_0.9fr_2fr_1.3fr_1fr_13rem]';
@endphp

<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative"
         x-data="inventoryPage({ newCountId: @js(session('new_count_id')) })"
         @confirm-action.window="ask($event.detail)">
        <x-background-glow />

        <div class="relative z-10 max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Encabezado -->
            <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('products.index') }}"
                       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                       title="Volver al catálogo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            Inventario físico
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @if ($isJefe)
                                Historial de conteos de todo el personal.
                            @else
                                Historial de tus conteos y diferencias de existencias.
                            @endif
                        </p>
                    </div>
                </div>

                <a href="{{ route('inventory-counts.create') }}"
                   class="group relative inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-emerald-600/25 transition-all duration-300 hover:from-emerald-500 hover:to-teal-500 hover:shadow-emerald-600/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Nuevo inventario</span>
                </a>
            </div>

            <!-- Pestañas Activos / Archivados (solo jefe) -->
            @if ($isJefe)
                <div class="mb-6 inline-flex rounded-2xl border border-gray-200/80 bg-white/80 p-1 shadow-sm backdrop-blur-md dark:border-gray-700/80 dark:bg-gray-800/80">
                    <a href="{{ route('inventory-counts.index') }}"
                       @class([
                           'rounded-xl px-4 py-2 text-sm font-semibold transition',
                           'bg-emerald-600 text-white shadow' => ! $showArchived,
                           'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => $showArchived,
                       ])>
                        Activos
                    </a>

                    <a href="{{ route('inventory-counts.index', ['archivados' => 1]) }}"
                       @class([
                           'rounded-xl px-4 py-2 text-sm font-semibold transition',
                           'bg-emerald-600 text-white shadow' => $showArchived,
                           'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' => ! $showArchived,
                       ])>
                        Archivados
                        @if ($archivedTotal > 0)
                            <span class="ml-1 rounded-full bg-black/10 px-2 py-0.5 text-xs dark:bg-white/10">{{ $archivedTotal }}</span>
                        @endif
                    </a>
                </div>
            @endif

            <!-- Mensajes -->
            @if (session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 p-4 text-sm font-medium shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-2xl bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 p-4 text-sm text-red-700 dark:text-red-300 shadow-sm">
                    <ul class="list-inside list-disc space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Listado -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl border border-white/20 dark:border-gray-700/50 overflow-hidden">

                @if ($isJefe && ! $showArchived && $counts->isNotEmpty())
                    <p class="border-b border-gray-200/60 px-5 py-3 text-xs text-gray-500 dark:border-gray-700/60 dark:text-gray-400 md:hidden">
                        Desliza una fila a la derecha para archivarla.
                    </p>
                @endif

                <!-- Encabezado de columnas (escritorio) -->
                <div class="hidden md:grid {{ $gridCols }} gap-4 bg-gray-50/80 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <div>Folio</div>
                    <div>Fecha</div>
                    <div>Descripción</div>
                    <div>Responsable</div>
                    <div>Estado</div>
                    <div class="text-right">{{ $isJefe ? 'Acciones' : '' }}</div>
                </div>

                @forelse ($counts as $count)
                    @php
                        $isApplied  = $count->applied_at !== null;
                        $isArchived = $count->archived_at !== null;
                        $totalItems   = (int) $count->items_count;
                        $countedItems = (int) $count->counted_items_count;
                        $isReady      = $countedItems > 0;

                        $canApply   = $isJefe && ! $isApplied && ! $isArchived;
                        $canArchive = $isJefe;

                        $statusClasses = match (true) {
                            $isApplied => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800',
                            $count->status === 'en_proceso' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800',
                            default => 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-300 border border-sky-200 dark:border-sky-800',
                        };

                        $applyPayload = [
                            'title'   => 'Aplicar inventario ' . $count->folio,
                            'message' => "Se ajustará el stock de los {$countedItems} producto(s) contados (de {$totalItems})."
                                . ($totalItems > $countedItems ? ' Los ' . ($totalItems - $countedItems) . ' sin contar conservarán su stock actual.' : ''),
                            'warning' => 'Esta acción modifica el stock del sistema y no se puede deshacer.',
                            'label'   => 'Sí, aplicar',
                            'tone'    => 'emerald',
                            'form'    => 'apply-form-' . $count->id,
                        ];

                        $archivePayload = $isArchived
                            ? [
                                'title'   => 'Restaurar inventario ' . $count->folio,
                                'message' => 'Volverá al historial activo y los inventaristas podrán verlo de nuevo.',
                                'warning' => null,
                                'label'   => 'Sí, restaurar',
                                'tone'    => 'indigo',
                                'form'    => 'archive-form-' . $count->id,
                            ]
                            : [
                                'title'   => 'Archivar inventario ' . $count->folio,
                                'message' => 'Se ocultará del historial de los inventaristas. Podrás restaurarlo desde la pestaña Archivados.',
                                'warning' => null,
                                'label'   => 'Sí, archivar',
                                'tone'    => 'amber',
                                'form'    => 'archive-form-' . $count->id,
                            ];
                    @endphp

                    <div id="count-{{ $count->id }}"
                         x-data="swipeRow({ enabled: @js($canArchive), payload: @js($archivePayload) })"
                         class="relative overflow-hidden border-b border-gray-200/60 last:border-b-0 dark:border-gray-700/60">

                        {{-- Capa de fondo que se revela al deslizar a la derecha (solo jefe) --}}
                        @if ($canArchive)
                            <div aria-hidden="true"
                                 class="absolute inset-0 flex items-center px-6 text-white {{ $isArchived ? 'bg-indigo-500' : 'bg-amber-500' }}">
                                <div x-show="x > 0"
                                     class="flex items-center gap-2 text-sm font-semibold transition-transform duration-150"
                                     :class="x >= threshold ? 'scale-110' : ''">
                                    @if ($isArchived)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a5 5 0 015 5v2M3 10l4-4m-4 4l4 4" />
                                        </svg>
                                        Restaurar
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                        </svg>
                                        Archivar
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Fila deslizable --}}
                        <div class="relative touch-pan-y bg-white px-5 py-4 transition-colors dark:bg-gray-800 md:px-6 grid gap-2 md:gap-4 {{ $gridCols }} md:items-center"
                             :class="[
                                 dragging ? 'select-none' : 'transition-transform duration-200 ease-out',
                                 newCountId === {{ $count->id }} ? 'ring-2 ring-inset ring-indigo-400/70' : ''
                             ]"
                             :style="`transform: translateX(${x}px)`"
                             @pointerdown="start($event)"
                             @pointermove="move($event)"
                             @pointerup="end()"
                             @pointercancel="cancel()">

                            {{-- Folio --}}
                            <div class="flex items-center gap-2.5 font-semibold">
                                <a href="{{ route('inventory-counts.show', $count) }}"
                                   class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition">
                                    {{ $count->folio }}
                                </a>

                                <template x-if="newCountId === {{ $count->id }}">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 animate-pulse border border-indigo-200 dark:border-indigo-800">
                                        ¡Nuevo!
                                    </span>
                                </template>
                            </div>

                            {{-- Fecha --}}
                            <div class="text-gray-600 dark:text-gray-300 font-medium">
                                <span class="text-xs text-gray-400 md:hidden">Fecha: </span>{{ $count->count_date->format('d/m/Y') }}
                                @if ($isArchived)
                                    <span class="block text-xs font-normal text-gray-400">Archivado {{ $count->archived_at->format('d/m/Y') }}</span>
                                @endif
                            </div>

                            {{-- Descripción --}}
                            <div class="text-gray-600 dark:text-gray-300">
                                {{ $count->description ?: 'Sin descripción' }}
                            </div>

                            {{-- Responsable --}}
                            <div class="text-gray-600 dark:text-gray-300 font-medium">
                                <span class="text-xs text-gray-400 md:hidden">Responsable: </span>{{ $count->user->name ?? 'Usuario eliminado' }}
                            </div>

                            {{-- Estado --}}
                            <div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClasses }}">
                                    {{ str_replace('_', ' ', ucfirst($count->status)) }}
                                </span>
                            </div>

                            {{-- Acciones --}}
                            <div class="flex flex-wrap items-center gap-2 pt-1 md:justify-end md:pt-0">
                                @if ($canApply)
                                    <button type="button"
                                            @if ($isReady)
                                                @click="$dispatch('confirm-action', @js($applyPayload))"
                                            @else
                                                disabled
                                                title="Aún no hay productos contados"
                                            @endif
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-emerald-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Aplicar
                                    </button>
                                @endif

                               
                            </div>
                        </div>

                        {{-- Formularios ocultos (solo jefe) --}}
                        @if ($canApply)
                            <form id="apply-form-{{ $count->id }}" method="POST"
                                  action="{{ route('inventory-counts.apply', $count) }}" class="hidden">
                                @csrf
                            </form>
                        @endif

                        @if ($canArchive)
                            <form id="archive-form-{{ $count->id }}" method="POST"
                                  action="{{ $isArchived ? route('inventory-counts.restore', $count) : route('inventory-counts.archive', $count) }}"
                                  class="hidden">
                                @csrf
                                @method('PATCH')
                            </form>
                        @endif
                    </div>

                @empty
                    <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                        @if ($showArchived)
                            No hay inventarios archivados.
                        @else
                            Todavía no hay inventarios físicos registrados.
                        @endif
                    </div>
                @endforelse

                <!-- Paginación -->
                @if ($counts->hasPages())
                    <div class="p-6 border-t border-gray-200/60 dark:border-gray-700/60 bg-white/40 dark:bg-gray-900/20">
                        {{ $counts->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Ventana de confirmación (aplicar, archivar, restaurar) --}}
        <template x-teleport="body">
            <div x-cloak
                 x-show="dialog.open"
                 x-transition.opacity
                 @keydown.escape.window="close()"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="display: none;"
                 role="dialog"
                 aria-modal="true">

                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>

                <div x-show="dialog.open"
                     x-transition
                     @click.stop
                     class="relative z-10 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">

                    <h2 class="text-lg font-bold text-gray-900 dark:text-white" x-text="dialog.title"></h2>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300" x-text="dialog.message"></p>

                    <div x-show="dialog.warning"
                         class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-medium text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
                         x-text="dialog.warning"></div>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button"
                                @click="close()"
                                class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancelar
                        </button>

                        <button type="button"
                                @click="accept()"
                                :disabled="busy"
                                :class="tones[dialog.tone] ?? tones.indigo"
                                class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                                x-text="busy ? 'Procesando…' : dialog.label"></button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {

            // Estado de la página: resaltado del nuevo conteo y ventana de confirmación
            Alpine.data('inventoryPage', ({ newCountId }) => ({
                newCountId,
                busy: false,
                dialog: { open: false, title: '', message: '', warning: null, label: '', tone: 'indigo', form: null },

                tones: {
                    emerald: 'bg-emerald-600 hover:bg-emerald-700',
                    amber: 'bg-amber-600 hover:bg-amber-700',
                    indigo: 'bg-indigo-600 hover:bg-indigo-700',
                },

                init() {
                    if (this.newCountId) {
                        setTimeout(() => {
                            document.getElementById('count-' + this.newCountId)
                                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }, 300);
                    }
                },

                ask(payload) {
                    this.busy = false;
                    this.dialog = { ...this.dialog, ...payload, open: true };
                },

                close() {
                    this.dialog.open = false;
                },

                accept() {
                    if (this.busy || !this.dialog.form) return;
                    this.busy = true;
                    document.getElementById(this.dialog.form)?.submit();
                },
            }));

            // Fila deslizable: a la derecha archiva o restaura (solo jefe)
            Alpine.data('swipeRow', ({ enabled, payload }) => ({
                x: 0,
                startX: 0,
                startY: 0,
                dragging: false,
                axis: null,
                threshold: 90,
                maxPull: 130,

                start(e) {
                    if (!enabled) return;
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

                    this.x = Math.max(0, Math.min(this.maxPull, dx));
                },

                end() {
                    if (!this.dragging) return;

                    const pulled = this.x;
                    const wasHorizontal = this.axis === 'h';

                    this.dragging = false;
                    this.axis = null;
                    this.x = 0;

                    if (wasHorizontal && pulled >= this.threshold) {
                        this.$dispatch('confirm-action', payload);
                    }
                },

                cancel() {
                    this.dragging = false;
                    this.axis = null;
                    this.x = 0;
                },
            }));
        });
    </script>
</x-app-layout>
