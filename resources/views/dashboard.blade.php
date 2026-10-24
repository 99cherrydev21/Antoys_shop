
<x-app-layout>


    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">


            <!-- Opciones del sistema -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                @if (Auth::user()->role?->name === 'Jefe')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Gestión de usuarios
                        </h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Administrar los usuarios y sus roles.
                        </p>
                    </div>
                @endif

                @if (in_array(Auth::user()->role?->name, ['Jefe', 'inventarista']))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <a href="{{ route('products.index') }}" >
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Inventario
                            </h4>
                            <p class="mt-2 text-gray-600 dark:text-gray-300">
                                Consultar existencias y gestionar productos.
                            </p>
                        </a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Reportes de inventario
                        </h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Consultar y preparar reportes de existencias.
                        </p>
                    </div>
                @endif

                @if (in_array(Auth::user()->role?->name, ['Jefe', 'vendedor']))
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Punto de venta
                        </h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Registrar ventas y consultar sus detalles.
                        </p>
                    </div>
                @endif

                @if (Auth::user()->role?->name === 'Jefe')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Reportes de ventas
                        </h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Consultar resultados diarios, mensuales y anuales.
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Control del sistema
                        </h4>
                        <p class="mt-2 text-gray-600 dark:text-gray-300">
                            Supervisar las operaciones administrativas.
                        </p>
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
