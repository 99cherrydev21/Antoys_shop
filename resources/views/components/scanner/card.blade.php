<div x-data="swipeRow()"
     class="relative overflow-hidden rounded-2xl border border-gray-200 shadow dark:border-gray-700">

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
    <article class="relative bg-white p-2.5 dark:bg-gray-800 sm:p-3"
             :style="{
                 transform: `translateX(${offset}px)`,
                 transition: tracking ? 'none' : 'transform 200ms ease'
             }"
             style="touch-action: pan-y;"
             @pointerdown="start($event)"
             @pointermove="move($event)"
             @pointerup="end()"
             @pointercancel="cancel()">

        <div class="flex items-start gap-3">

            <!-- Miniatura -->
            <div class="shrink-0">
                <template x-if="item.image">
                    <img :src="item.image" :alt="item.name" width="56" height="56"
                         class="h-14 w-14 rounded-xl border border-gray-200 bg-gray-100 object-cover
                                dark:border-gray-700 dark:bg-gray-900"
                         loading="lazy" decoding="async" draggable="false">
                </template>
                <template x-if="!item.image">
                    <div class="flex h-14 w-14 flex-col items-center justify-center rounded-xl
                                border border-dashed border-gray-300 bg-gray-100 text-gray-400
                                dark:border-gray-700 dark:bg-gray-900">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M3 16l5-5 4 4 4-6 5 7M5 4h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z"/>
                        </svg>
                        <span class="text-[9px]">Sin foto</span>
                    </div>
                </template>
            </div>

            <!-- Información -->
            <div class="min-w-0 flex-1">
                <h3 class="line-clamp-2 break-words text-sm font-bold leading-tight text-gray-800 dark:text-white"
                    x-text="item.name"></h3>
                <p class="truncate text-[11px] text-gray-500 dark:text-gray-400" x-text="item.sku"></p>

                <!-- Existencias -->
                <span x-show="showStock"
                      class="mt-1 inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    <span x-text="stockLabel + ':'"></span>
                    <span class="ml-1" x-text="item.stock"></span>
                </span>

                <!-- Precio (solo en venta): al tocarlo se abre el popup para cambiarlo -->
                <template x-if="item.base_price != null">
                    <div class="mt-1">
                        <button type="button"
                                @pointerdown.stop
                                @click.stop="openPrice(item)"
                                class="inline-flex items-center gap-1 rounded-lg border px-2 py-0.5 text-xs font-bold tabular-nums
                                       transition focus:outline-none focus:ring-2 focus:ring-teal-500"
                                :class="priceChanged(item)
                                    ? 'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-900 dark:text-amber-100'
                                    : 'border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-900 dark:text-teal-100'"
                                :aria-label="'Cambiar precio de ' + item.name">
                            <span x-text="money(item.price)"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.768-6.768a2.5 2.5 0 113.536 3.536L12.536 16.536 8 18l1-5z"/>
                            </svg>
                        </button>
                        <span x-show="priceChanged(item)" x-cloak
                              class="ml-1 text-[10px] text-amber-600 dark:text-amber-400"
                              x-text="'antes ' + money(item.base_price)"></span>
                    </div>
                </template>
            </div>

            <!-- Esquina: cantidad compacta (+ subtotal en venta) -->
            <div class="flex shrink-0 flex-col items-end gap-1">
                <div class="flex items-center gap-1">
                    <button type="button"
                            @mousedown.prevent
                            @click="moved || decrease(item)"
                            :class="item.quantity <= minQuantity ? 'opacity-60' : ''"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300
                                   bg-white text-lg font-semibold leading-none text-gray-700 transition
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
                           class="h-8 w-10 rounded-lg border px-1 text-center text-sm font-bold tabular-nums
                                  transition-colors focus:outline-none focus:ring-2
                                  [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                           :class="(item.flash || exceeds(item))
                                ? 'border-red-500 bg-red-50 text-red-600 focus:ring-red-500 dark:bg-red-900 dark:text-red-200'
                                : 'border-gray-300 bg-white text-gray-800 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white'"
                           aria-label="Cantidad">

                    <button type="button"
                            @mousedown.prevent
                            @click="moved || increase(item)"
                            class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-300
                                   bg-white text-lg font-semibold leading-none text-gray-700 transition
                                   hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500
                                   dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            aria-label="Aumentar cantidad">
                        +
                    </button>
                </div>

                <template x-if="item.base_price != null">
                    <span class="text-sm font-extrabold tabular-nums text-gray-800 dark:text-white"
                          x-text="money((Number(item.price) || 0) * (Number(item.quantity) || 0))"></span>
                </template>
            </div>
        </div>

        <!-- Aviso de la tarjeta -->
        <p x-show="warnText(item)" x-cloak x-transition
           class="mt-1.5 text-xs font-medium text-red-600 dark:text-red-400"
           x-text="warnText(item)"></p>
    </article>
</div>
