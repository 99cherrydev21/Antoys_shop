
<x-app-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 relative">
        <x-background-glow />

        <div class="relative z-10 max-w-5xl mx-auto px-4 py-8 sm:px-6 lg:px-8">

            <div class="mb-6">
                <a href="{{ route('products.show', $product) }}"
                   class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                    &larr; Volver al producto
                </a>

                <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                    Movimientos de inventario
                </h1>

                <p class="mt-1 text-gray-600 dark:text-gray-400">
                    {{ $product->name }} · {{ $product->sku }}
                </p>

                <div class="mt-4 rounded-xl bg-white dark:bg-gray-800 p-4 shadow">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Existencias actuales
                    </p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ $product->stock }}
                    </p>
                </div>
            </div>

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-100 p-3 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-8 rounded-2xl bg-white dark:bg-gray-800 p-5 shadow">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Registrar movimiento
                </h2>

                <form method="POST"
                      action="{{ route('inventory.movements.store', $product) }}"
                      class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <label for="type"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tipo de movimiento
                        </label>

                        <select id="type" name="type" required
                                class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            <option value="entrada" @selected(old('type') === 'entrada')>
                                Entrada: agregar existencias
                            </option>
                            <option value="salida" @selected(old('type') === 'salida')>
                                Salida: retirar existencias
                            </option>
                        </select>

                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="quantity"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Cantidad
                        </label>

                        <input id="quantity" name="quantity" type="number"
                               min="1" max="4294967295" required
                               value="{{ old('quantity') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">

                        @error('quantity')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reason"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Motivo (opcional)
                        </label>

                        <input id="reason" name="reason" type="text"
                               maxlength="255" value="{{ old('reason') }}"
                               placeholder="Ej. Compra a proveedor, mercancía dañada..."
                               class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white">

                        @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700 sm:w-auto">
                        Guardar movimiento
                    </button>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow">
                <div class="border-b border-gray-200 dark:border-gray-700 p-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Historial
                    </h2>
                </div>

                @if ($movements->isEmpty())
                    <p class="p-6 text-gray-500 dark:text-gray-400">
                        Todavía no hay movimientos registrados.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="p-3">Fecha</th>
                                    <th class="p-3">Movimiento</th>
                                    <th class="p-3">Cantidad</th>
                                    <th class="p-3">Antes</th>
                                    <th class="p-3">Después</th>
                                    <th class="p-3">Usuario</th>
                                    <th class="p-3">Motivo</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-800 dark:text-gray-200">
                                @foreach ($movements as $movement)
                                    <tr>
                                        <td class="p-3 whitespace-nowrap">
                                            {{ $movement->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="p-3">
                                            <span class="rounded-full px-2 py-1 text-xs font-semibold
                                                {{ $movement->type === 'entrada'
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="p-3">{{ $movement->quantity }}</td>
                                        <td class="p-3">{{ $movement->stock_before }}</td>
                                        <td class="p-3">{{ $movement->stock_after }}</td>
                                        <td class="p-3">{{ $movement->user?->name ?? 'Usuario eliminado' }}</td>
                                        <td class="p-3">{{ $movement->reason ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
