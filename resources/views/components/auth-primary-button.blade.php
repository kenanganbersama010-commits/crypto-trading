@props(['disabled' => false])

<button @disabled($disabled) {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold uppercase tracking-wide text-white focus:outline-none focus:ring-2 focus:ring-violet-700 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
