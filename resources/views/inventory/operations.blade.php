<x-app-layout>
    <x-scanner.page
        title="Gestionar inventario"
        subtitle="Escanea los productos y registra todos los movimientos de una vez."
        :back="route('products.index')"
        :action="route('inventory.operations.store')"
        :lookup="route('inventory.operations.lookup')"
        type="entrada"
        placeholder="ANT-0000001">

        <x-slot name="aside">
            <x-scanner.type-selector />
        </x-slot>
    </x-scanner.page>
</x-app-layout>
