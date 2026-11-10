@php
    $user = Auth::user();
    $roleName = (string) $user->role?->name;
    $role = mb_strtolower($roleName);

    // Saludo contextual según la hora
    $hour = (int) now()->format('G');
    $greeting = match (true) {
        $hour < 12 => 'Buenos días',
        $hour < 19 => 'Buenas tardes',
        default    => 'Buenas noches',
    };

    $firstName = \Illuminate\Support\Str::of($user->name)->explode(' ')->first();

    // Cada opción: grupo, título, descripción, ruta, roles, colores pastel e icono
    $items = [
        [
            'group' => 'Operación',
            'emoji' => '',
            'title' => 'Punto de venta',
            'desc'  => '',
            'route' => 'sales.history',
            'roles' => ['jefe', 'vendedor'],
            'grad'  => 'from-pink-300 to-rose-400',
            'ring'  => 'ring-pink-200/60 dark:ring-pink-500/20',
            'glow'  => 'shadow-pink-300/40 dark:shadow-pink-500/20',
            'text'  => 'text-pink-500 dark:text-pink-300',
            'border'=> 'border-pink-200/70 dark:border-pink-500/20',
            'hover' => 'hover:border-pink-300 dark:hover:border-pink-400/60',
            'icon'  => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>',
            'cardEmoji' => '',
        ],
        [
            'group' => 'Operación',
            'emoji' => '',
            'title' => 'Inventario',
            'desc'  => '',
            'route' => 'products.index',
            'roles' => ['jefe', 'inventarista'],
            'grad'  => 'from-sky-300 to-blue-400',
            'ring'  => 'ring-sky-200/60 dark:ring-sky-500/20',
            'glow'  => 'shadow-sky-300/40 dark:shadow-sky-500/20',
            'text'  => 'text-sky-500 dark:text-sky-300',
            'border'=> 'border-sky-200/70 dark:border-sky-500/20',
            'hover' => 'hover:border-sky-300 dark:hover:border-sky-400/60',
            'icon'  => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/>',
            'cardEmoji' => '',
        ],
        [
            'group' => 'Reportes',
            'emoji' => '',
            'title' => 'Reportes de ventas',
            'desc'  => '',
            'route' => 'reports.sales',
            'roles' => ['jefe'],
            'grad'  => 'from-emerald-300 to-teal-400',
            'ring'  => 'ring-emerald-200/60 dark:ring-emerald-500/20',
            'glow'  => 'shadow-emerald-300/40 dark:shadow-emerald-500/20',
            'text'  => 'text-emerald-500 dark:text-emerald-300',
            'border'=> 'border-emerald-200/70 dark:border-emerald-500/20',
            'hover' => 'hover:border-emerald-300 dark:hover:border-emerald-400/60',
            'icon'  => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
            'cardEmoji' => '',
        ],
        [
            'group' => 'Reportes',
            'emoji' => '',
            'title' => 'Reportes de inventario',
            'desc'  => '',
            'route' => 'reports.inventory',
            'roles' => ['jefe', 'inventarista'],
            'grad'  => 'from-cyan-300 to-teal-400',
            'ring'  => 'ring-cyan-200/60 dark:ring-cyan-500/20',
            'glow'  => 'shadow-cyan-300/40 dark:shadow-cyan-500/20',
            'text'  => 'text-cyan-500 dark:text-cyan-300',
            'border'=> 'border-cyan-200/70 dark:border-cyan-500/20',
            'hover' => 'hover:border-cyan-300 dark:hover:border-cyan-400/60',
            'icon'  => '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 12h6M9 16h6"/>',
            'cardEmoji' => '',
        ],
        [
            'group' => 'Administración',
            'emoji' => '',
            'title' => 'Gestión de usuarios',
            'desc'  => '',
            'route' => 'users.index',
            'roles' => ['jefe'],
            'grad'  => 'from-purple-300 to-violet-400',
            'ring'  => 'ring-purple-200/60 dark:ring-purple-500/20',
            'glow'  => 'shadow-purple-300/40 dark:shadow-purple-500/20',
            'text'  => 'text-purple-500 dark:text-purple-300',
            'border'=> 'border-purple-200/70 dark:border-purple-500/20',
            'hover' => 'hover:border-purple-300 dark:hover:border-purple-400/60',
            'icon'  => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>',
            'cardEmoji' => '',
        ],
        [
            'group' => 'Administración',
            'emoji' => '',
            'title' => 'Control del sistema',
            'desc'  => '',
            'route' => 'maintenance.index',
            'roles' => ['jefe'],
            'grad'  => 'from-orange-300 to-amber-400',
            'ring'  => 'ring-orange-200/60 dark:ring-orange-500/20',
            'glow'  => 'shadow-orange-300/40 dark:shadow-orange-500/20',
            'text'  => 'text-orange-500 dark:text-orange-300',
            'border'=> 'border-orange-200/70 dark:border-orange-500/20',
            'hover' => 'hover:border-orange-300 dark:hover:border-orange-400/60',
            'icon'  => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>',
            'cardEmoji' => '',
        ],
    ];

    $accessible = collect($items)->filter(fn ($item) => in_array($role, $item['roles'], true));
    $groups     = $accessible->groupBy('group');

    $roleLabels = [
        'jefe'         => ['Jefe', ''],
        'vendedor'     => ['Vendedor', ''],
        'inventarista' => ['Inventarista', ''],
    ];
    [$roleLabel, $roleEmoji] = $roleLabels[$role] ?? [ucfirst($role ?: 'Usuario'), '✨'];

    $n = 0;
@endphp

<x-app-layout>
    <div class="relative min-h-screen bg-gradient-to-br from-pink-50 via-purple-50 to-sky-50 transition-colors duration-300 dark:from-gray-950 dark:via-gray-950 dark:to-gray-900">
        <x-background-glow />

        {{-- Burbujas decorativas de fondo (solo light mode, muy sutiles) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-pink-200/40 blur-3xl dark:bg-pink-500/5"></div>
            <div class="absolute top-1/3 -right-20 h-72 w-72 rounded-full bg-purple-200/30 blur-3xl dark:bg-purple-500/5"></div>
            <div class="absolute -bottom-20 left-1/4 h-56 w-56 rounded-full bg-sky-200/40 blur-3xl dark:bg-sky-500/5"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">

            {{-- ═══════════════════════════════════════════════════════
                 HERO: Saludo cute + Rol
            ═══════════════════════════════════════════════════════ --}}
            <header class="mb-6 sm:mb-8">

            </header>

            {{-- ═══════════════════════════════════════════════════════
                 GRID DE MÓDULOS AGRUPADOS
            ═══════════════════════════════════════════════════════ --}}
            @forelse ($groups as $groupName => $groupItems)
                <section class="mb-7 last:mb-0 sm:mb-9" aria-labelledby="group-{{ $loop->index }}">
                    <div class="mx-auto mb-3 flex max-w-md items-center gap-2 sm:max-w-2xl lg:max-w-none">
                        <h2 id="group-{{ $loop->index }}"
                            class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                            <span aria-hidden="true">{{ $groupItems->first()['emoji'] }}</span>
                            {{ $groupName }}
                        </h2>
                        <span class="h-px flex-1 bg-gradient-to-r from-pink-200 via-purple-200 to-transparent dark:from-pink-500/20 dark:via-purple-500/20" aria-hidden="true"></span>
                    </div>

                    <div class="mx-auto grid max-w-md grid-cols-1 gap-4 sm:max-w-2xl sm:grid-cols-2 sm:gap-5 lg:max-w-none lg:grid-cols-3">
                        @foreach ($groupItems as $item)
                            @php
                                $tag        = $item['route'] ? 'a' : 'div';
                                $delay      = ($n++) * 60;
                                $isDisabled = !$item['route'];
                            @endphp

                            <{{ $tag }}
                                @if ($item['route']) href="{{ route($item['route']) }}" @else aria-disabled="true" @endif
                                style="animation-delay: {{ $delay }}ms"
                                @if ($isDisabled) tabindex="0" aria-label="{{ $item['title'] }} — próximamente" @endif
                                class="dash-card group relative flex min-h-[10rem] flex-col overflow-hidden rounded-3xl border p-4 sm:min-h-[11rem] sm:p-5
                                       {{ $isDisabled
                                           ? 'cursor-not-allowed border-gray-200 bg-gray-50/70 opacity-75 dark:border-gray-800 dark:bg-gray-900/60'
                                           : 'bg-white/90 shadow-sm shadow-pink-100/40 backdrop-blur-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-pink-200/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-pink-400/30 active:scale-[0.98] dark:bg-gray-800/90 dark:shadow-none dark:hover:shadow-pink-500/10 ' . $item['border'] . ' ' . $item['hover'] }}">

                                {{-- Ilustración de fondo decorativa (color pastel del módulo) --}}
                                <div class="pointer-events-none absolute -right-6 -bottom-6 transition duration-500 group-hover:-rotate-6 group-hover:scale-110" aria-hidden="true">
                                    <svg class="h-28 w-28 sm:h-36 sm:w-36 {{ $item['text'] }} opacity-[0.08] dark:opacity-[0.1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                        {!! $item['icon'] !!}
                                    </svg>
                                </div>

                                {{-- Emoji flotante decorativo (top-right) --}}
                                <span class="absolute right-3 top-3 text-lg opacity-60 transition duration-300 group-hover:scale-125 group-hover:opacity-100 sm:text-xl" aria-hidden="true">
                                    {{ $item['cardEmoji'] }}
                                </span>

                                {{-- Icono principal con aura pastel --}}
                                <span class="relative flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br {{ $item['grad'] }} text-white shadow-lg {{ $item['glow'] }} ring-4 {{ $item['ring'] }} sm:h-14 sm:w-14">
                                    <svg class="h-6 w-6 sm:h-7 sm:w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        {!! $item['icon'] !!}
                                    </svg>
                                </span>

                                {{-- Contenido --}}
                                <div class="relative mt-3 flex flex-1 flex-col justify-end">
                                    <h3 class="text-sm font-bold leading-tight text-gray-800 dark:text-white sm:text-base">
                                        {{ $item['title'] }}
                                    </h3>
                                    <p class="mt-1 text-xs leading-snug text-gray-500 dark:text-gray-400 sm:text-[13px]">
                                        {{ $item['desc'] }}
                                    </p>

                                    {{-- Footer de la tarjeta --}}
                                    <div class="mt-2.5 flex items-center justify-between">
                                        @if ($item['route'])
                                            <span class="inline-flex items-center gap-1 text-xs font-bold {{ $item['text'] }}">

                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-pink-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-pink-500 dark:bg-pink-500/15 dark:text-pink-300">
                                                <span class="h-1 w-1 rounded-full bg-pink-400 dark:bg-pink-300"></span>
                                                Pronto
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="mx-auto max-w-md rounded-3xl border-2 border-dashed border-pink-200 bg-white/80 px-6 py-16 text-center backdrop-blur-sm sm:max-w-2xl dark:border-pink-500/30 dark:bg-gray-800/80">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-pink-100 text-pink-400 dark:bg-pink-500/15 dark:text-pink-300">
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        </svg>
                    </div>
                    <p class="mt-4 font-bold text-gray-800 dark:text-white">Tu usuario aún no tiene opciones asignadas</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pide al jefe que revise tu rol.</p>
                </div>
            @endforelse

        </div>
    </div>

    <style>
        @keyframes dash-in {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .dash-card { animation: dash-in .4s cubic-bezier(0.16, 1, 0.3, 1) both; }

        @media (prefers-reduced-motion: reduce) {
            .dash-card { animation: none; }
            .dash-card, .dash-card * { transition: none !important; }
        }
    </style>
</x-app-layout>
