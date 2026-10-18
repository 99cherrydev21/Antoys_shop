<x-app-layout>
    <!-- Contenedor con fondo adaptable y tus destellos -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-3xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

            <!-- Tarjeta contenedora principal Glassmorphism -->
            <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl shadow-2xl rounded-3xl p-6 sm:p-8 border border-white/20 dark:border-gray-700/50">

                <!-- Encabezado -->
<!-- Encabezado con botón de retorno integrado -->
<div class="mb-8 border-b border-gray-200/80 dark:border-gray-700/80 pb-5 flex items-center gap-4">
    <a href="{{ route('products.index') }}"
       class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border border-gray-200/80 dark:border-gray-700/80 text-gray-700 dark:text-gray-200 shadow-md transition-all duration-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:scale-105"
       title="Volver al catálogo">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>

    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Registrar producto</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Ingresa los datos para dar de alta un nuevo producto en el inventario.</p>
    </div>
</div>

                <!-- Manejo de Errores -->
                @if ($errors->any())
                    <div class="mb-6 bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 p-4 rounded-2xl text-sm">
                        <p class="font-semibold mb-1">Por favor corrige los siguientes errores:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Formulario -->
                <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-5"
                      x-data="{
                          images: [],
                          addImages(e) {
                              const files = Array.from(e.target.files);
                              files.forEach(file => {
                                  if (this.images.length < 5) {
                                      this.images.push({
                                          file: file,
                                          url: URL.createObjectURL(file)
                                      });
                                  }
                              });
                              e.target.value = '';
                              this.updateInputFiles();
                          },
                          removeImage(index) {
                              this.images.splice(index, 1);
                              this.updateInputFiles();
                          },
                          updateInputFiles() {
                              const dataTransfer = new DataTransfer();
                              this.images.forEach(img => {
                                  dataTransfer.items.add(img.file);
                              });
                              this.$refs.realInput.files = dataTransfer.files;
                          }
                      }">
                    @csrf

                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nombre</label>
                        <input
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            class="w-full bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        >
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="w-full bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                        >{{ old('description') }}</textarea>
                    </div>

                    <!-- Grid para Precio y Stock -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Precio -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Precio ($)</label>
                            <input
                                id="price"
                                type="number"
                                name="price"
                                min="0"
                                step="0.01"
                                value="{{ old('price') }}"
                                required
                                class="w-full bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                            >
                        </div>

                        <!-- Existencias iniciales -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Existencias iniciales</label>
                            <input
                                id="stock"
                                type="number"
                                name="stock"
                                min="0"
                                step="1"
                                value="{{ old('stock', 0) }}"
                                required
                                class="w-full bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                            >
                        </div>
                    </div>

                    <!-- Sección de Fotografías Interactiva -->
                    <div class="mt-4 bg-gray-50/50 dark:bg-gray-900/40 p-4 rounded-2xl border border-gray-200/60 dark:border-gray-700/60">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Fotografías del producto
                        </label>

                        <!-- Botón para abrir la cámara o galería -->
                        <div class="flex items-center justify-between">
                            <label class="cursor-pointer bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 px-4 py-2.5 rounded-xl font-medium text-sm transition flex items-center gap-2 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Tomar foto / Seleccionar
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    capture="environment"
                                    multiple
                                    @change="addImages"
                                    class="hidden"
                                >
                            </label>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium" x-text="images.length + ' de 5 imágenes agregadas'"></span>
                        </div>

                        <!-- Input real oculto que Laravel procesará en el backend -->
                        <input type="file" name="images[]" multiple class="hidden" x-ref="realInput">

                        <!-- Contenedor con scroll horizontal (flex-nowrap + overflow-x-auto) -->
                        <div class="flex flex-nowrap overflow-x-auto gap-3 mt-3 pb-2 w-full" x-show="images.length > 0">
                            <template x-for="(img, index) in images" :key="index">
                                <div class="relative w-20 h-20 flex-shrink-0">
                                    <div class="w-full h-full rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden relative">
                                        <img :src="img.url" class="w-full h-full object-cover">
                                        <span x-show="index === 0" class="absolute bottom-0 inset-x-0 bg-indigo-600/90 text-white text-[9px] text-center font-bold py-0.5 z-10">
                                            Principal
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeImage(index)"
                                        class="absolute -top-1.5 -right-1.5 bg-red-600 hover:bg-red-700 text-white rounded-full p-1 shadow-md z-30 transition transform hover:scale-110"
                                        title="Eliminar foto"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-200/60 dark:border-gray-700/60">



                        <button
                            type="submit"
                            class="w-full sm:w-auto bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white px-6 py-3 rounded-xl font-semibold transition-all duration-300 shadow-lg shadow-indigo-600/30 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            Guardar producto
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
