<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full inline-flex items-center justify-center px-6 py-3.5 bg-gray-900 dark:bg-gray-100 border border-transparent rounded-2xl font-semibold text-sm text-white dark:text-gray-900 uppercase tracking-wider hover:bg-gray-800 dark:hover:bg-white/90 focus:bg-gray-800 dark:focus:bg-white active:bg-black dark:active:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-gray-900/10 dark:focus:ring-gray-100/10 transition-all duration-200 shadow-md']) }}>
    {{ $slot }}
</button>
