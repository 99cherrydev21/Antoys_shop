<div x-data="swipeRow()"
     class="relative overflow-hidden rounded-2xl border border-gray-200 shadow-lg dark:border-gray-700">

    <!-- QUITAR (detrás; se ve al deslizar a la izquierda) -->
    <button type="button"
            @click="remove(item.id)"
            class="absolute inset-y-0 right-0 flex flex-col items-center justify-center gap-1
                   bg-red-600 text-xs font-semibold text-white transition-opacity
                   focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
            :style="{ width: W + 'px' }"
            :class="offset < 0 ? 'opacity-100' : 'opacity-0 pointer-events-none'"
            :tabindex="offset < 0 ? 0 : -1"
            :aria-label="'Quitar ' + item.name">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
        </svg>
        <span>Quitar</span>
    </button>

    <!-- Tarjeta deslizable -->
    <article class="relative bg-white p-3 dark:bg-gray-800 sm:p-4"
             :style="{
                 transform: `translateX(${offset}px)`,
                 transition: tracking ? 'none' : 'transform 200ms ease'
             }"
             style="touch-action: pan-y;"
             @pointerdown="start($event)"
             @pointermove="move($event)"
             @pointerup="end()"
             @pointercancel="cancel()">

        <div class="flex items-center gap-3">

            <!-- Miniatura -->
            <div class="shrink-0">
                <template x-if="item.image">
                    <img :src="item.image" :alt="item.name" width="80" height="80"
                         class="h-20 w-20 rounded-xl border border-gray-200 bg-gray-100 object-cover
                                dark:border-gray-700 dark:bg-gray-900"
                         loading="lazy" decoding="async" draggable="false">
                </template>
                <template x-if="!item.image">
                    <div class="flex h-20 w-20 flex-col items-center justify-center rounded-xl
                                border border-dashed border-gray-300 bg-gray-100 text-gray-400
                                dark:border-gray-700 dark:bg-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 16l5-5 4 4 4-6 5 7M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        <span class="mt-0.5 text-[10px]">Sin foto</span>
                    </div>
                </template>
            </div>

            <!-- Información -->
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-bold text-gray-800 dark:text-white" x-text="item.name"></h3>
                <p class="truncate text-xs text-gray-500 dark:text-gray-400" x-text="item.sku"></p>

                <!-- Badge de stock modificado -->
                <span x-show="showStock"
                      class="mt-2 inline-flex items-center rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    <span x-text="stockLabel + ':'"></span>
                    <span class="ml-1" x-text="item.stock"></span>
                </span>
            </div>
        </div>

        <!-- Cantidad + borrar -->
        <div class="mt-3 flex items-center justify-between gap-3 border-t border-gray-200 pt-3 dark:border-gray-700">
            <span class="text-sm text-gray-500 dark:text-gray-400">Cantidad</span>

            <div class="flex items-center gap-2">
                <button type="button"
                        @mousedown.prevent
                        @click="moved || decrease(item)"
                        :class="item.quantity <= minQuantity ? 'opacity-60' : ''"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300
                               bg-white text-xl font-semibold text-gray-700 transition
                               hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500
                               dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Disminuir cantidad">
                    −
                </button>

                <input type="number"
                       inputmode="numeric"
                       :min="minQuantity"
                       max="999999"
                       x-model.number="item.quantity"
                       @input="validateQuantity(item)"
                       @change="validateQuantity(item, true)"
                       @keydown.enter.prevent="$event.target.blur()"
                       class="h-10 w-16 rounded-xl border px-2 text-center text-lg font-bold tabular-nums
                              transition-colors focus:outline-none focus:ring-2"
                       :class="(item.flash || exceeds(item))
                            ? 'border-red-500 bg-red-50 text-red-600 focus:ring-red-500 dark:bg-red-900 dark:text-red-200'
                            : 'border-gray-300 bg-white text-gray-800 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white'"
                       aria-label="Cantidad">

                <button type="button"
                        @mousedown.prevent
                        @click="moved || increase(item)"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-300
                               bg-white text-xl font-semibold text-gray-700 transition
                               hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500
                               dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Aumentar cantidad">
                    +
                </button>

               
            </div>
        </div>

        <!-- Aviso de la tarjeta -->
        <p x-show="warnText(item)" x-cloak x-transition
           class="mt-2 text-xs font-medium text-red-600 dark:text-red-400"
           x-text="warnText(item)"></p>
    </article>
</div>
