<x-app-layout>
    <!-- Contenedor con fondo adaptable y destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Tarjeta contenedora principal Glassmorphism -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-6 sm:p-10 border border-white/20 dark:border-gray-700/50">

                <!-- Encabezado con botón de retorno al lado -->
                <div class="mb-8 border-b border-gray-200/80 dark:border-gray-700/80 pb-5 flex items-center gap-4">
                    <a href="{{ route('products.index') }}"
                       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
                       title="Volver al catálogo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar producto</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Modifica los detalles del producto en el inventario.</p>
                    </div>
                </div>

                <!-- Manejo de Errores -->
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 p-4 rounded-2xl text-sm shadow-sm">
                        <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Formulario -->
                <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6"
                      x-data="{
                          existingImages: @js($product->images),
                          removedImageIds: [],
                          newImages: [],

                          removeExisting(id, index) {
                              this.removedImageIds.push(id);
                              this.existingImages.splice(index, 1);
                          },
                          addNewImages(e) {
                              const files = Array.from(e.target.files);
                              files.forEach(file => {
                                  if ((this.existingImages.length + this.newImages.length) < 5) {
                                      this.newImages.push({
                                          file: file,
                                          url: URL.createObjectURL(file)
                                      });
                                  }
                              });
                              e.target.value = '';
                              this.updateInputFiles();
                          },
                          removeNewImage(index) {
                              this.newImages.splice(index, 1);
                              this.updateInputFiles();
                          },
                          updateInputFiles() {
                              const dataTransfer = new DataTransfer();
                              this.newImages.forEach(img => {
                                  dataTransfer.items.add(img.file);
                              });
                              this.$refs.realInput.files = dataTransfer.files;
                          }
                      }">
                    @csrf
                    @method('PUT')

                    <!-- Inputs ocultos para enviar los IDs de las imágenes a eliminar -->
                    <template x-for="id in removedImageIds" :key="id">
                        <input type="hidden" name="remove_images[]" :value="id">
                    </template>

                    <!-- SKU / ID (Readonly) -->
                    <div>
                        <label for="sku" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">SKU / ID</label>
                        <input
                            id="sku"
                            name="sku"
                            value="{{ old('sku', $product->sku) }}"
                            readonly
                            class="w-full bg-gray-100 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 focus:outline-none cursor-not-allowed shadow-sm"
                        >
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nombre del producto</label>
                        <input
                            id="name"
                            name="name"
                            value="{{ old('name', $product->name) }}"
                            required
                            class="w-full bg-white dark:bg-gray-900/80 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm"
                        >
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Descripción <span class="text-xs font-normal text-gray-400">(Opcional)</span></label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full bg-white dark:bg-gray-900/80 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm resize-none"
                        >{{ old('description', $product->description) }}</textarea>
                    </div>

                    <!-- Grid para Precio y Cantidad -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Precio -->
                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Precio unitario ($)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 font-semibold">$</div>
                                <input
                                    id="price"
                                    type="number"
                                    name="price"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('price', $product->price) }}"
                                    required
                                    class="w-full bg-white dark:bg-gray-900/80 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-2xl pl-8 pr-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm"
                                >
                            </div>
                        </div>

                        <!-- Cantidad / Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Cantidad</label>
                            <input
                                id="stock"
                                type="number"
                                name="stock"
                                min="0"
                                step="1"
                                value="{{ old('stock', $product->stock) }}"
                                required
                                class="w-full bg-white dark:bg-gray-900/80 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition shadow-sm"
                            >
                        </div>
                    </div>

                    <!-- Sección de Fotografías -->
                    <div class="pt-3 border-t border-gray-200/60 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                Fotografías del producto
                            </label>
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400" x-text="(existingImages.length + newImages.length) + ' / 5 fotografías'"></span>
                        </div>

                        <!-- Botón para adjuntar nuevas fotos -->
                        <div class="flex items-center gap-3">
                            <label class="cursor-pointer group relative inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border-2 border-dashed border-gray-300 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 text-gray-600 dark:text-gray-300 text-sm font-medium transition-all duration-300 w-full justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>Toca para agregar más fotos o tomar una nueva</span>
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    capture="environment"
                                    multiple
                                    @change="addNewImages"
                                    class="hidden"
                                >
                            </label>
                        </div>

                        <!-- Input real sincronizado con DataTransfer para Laravel (asegurando el nombre images[]) -->
                        <input type="file" name="images[]" multiple class="hidden" x-ref="realInput">

                        <!-- Miniaturas -->
                        <div class="flex flex-nowrap overflow-x-auto gap-3 mt-4 pb-2 w-full" x-show="existingImages.length > 0 || newImages.length > 0">

                            <!-- Imágenes ya existentes -->
                            <template x-for="(img, index) in existingImages" :key="'existing-' + img.id">
                                <div class="relative w-24 h-24 flex-shrink-0">
                                    <div class="w-full h-full rounded-2xl border-2 border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-900 shadow-md overflow-hidden relative">
                                        <img :src="'{{ asset('storage') }}/' + img.path" class="w-full h-full object-cover">
                                        <span x-show="index === 0" class="absolute bottom-0 inset-x-0 bg-indigo-600/90 text-white text-[10px] text-center font-bold py-0.5 z-10 backdrop-blur-sm">
                                            Principal
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeExisting(img.id, index)"
                                        class="absolute -top-2 -right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-1.5 shadow-lg z-30 transition transform hover:scale-110 focus:outline-none"
                                        title="Eliminar foto"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>

                            <!-- Imágenes nuevas agregadas -->
                            <template x-for="(img, index) in newImages" :key="'new-' + index">
                                <div class="relative w-24 h-24 flex-shrink-0">
                                    <div class="w-full h-full rounded-2xl border-2 border-emerald-400 dark:border-emerald-600 bg-gray-100 dark:bg-gray-900 shadow-md overflow-hidden relative">
                                        <img :src="img.url" class="w-full h-full object-cover">
                                        <span class="absolute top-1 left-1 bg-emerald-600 text-white text-[9px] px-1.5 py-0.5 rounded-md font-bold z-10">
                                            Nueva
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeNewImage(index)"
                                        class="absolute -top-2 -right-2 bg-red-600 hover:bg-red-700 text-white rounded-full p-1.5 shadow-lg z-30 transition transform hover:scale-110 focus:outline-none"
                                        title="Eliminar foto"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>

                        </div>
                    </div>

                    <!-- Botón de acción Guardar cambios -->
                    <div class="pt-6 border-t border-gray-200/60 dark:border-gray-700/60 flex justify-end">
                        <button
                            type="submit"
                            class="group relative inline-flex items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-indigo-600/25 transition-all duration-300 hover:from-indigo-500 hover:to-violet-500 hover:shadow-indigo-600/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 w-full sm:w-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Guardar cambios</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
