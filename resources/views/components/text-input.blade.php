@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:border-gray-900 dark:focus:border-gray-100 focus:ring-4 focus:ring-gray-900/10 dark:focus:ring-gray-100/10 rounded-2xl shadow-sm px-4 py-3 text-sm transition-all duration-200 outline-none']) }}>
