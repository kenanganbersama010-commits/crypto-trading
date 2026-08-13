@props(['href' => '#', 'icon' => null, 'active' => false])

<a
    href="{{ $href }}"
    @click="sidebarOpen = false"
    @if ($active) aria-current="page" @endif
    :class="sidebarCollapsed ? 'lg:justify-center' : ''"
    {{ $attributes->merge(['class' =>
        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-100 '
        . ($active
            ? 'bg-white text-violet-900 shadow-sm'
            : 'text-violet-100 hover:bg-violet-700/50 hover:text-white')
    ]) }}
>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $active ? 'text-violet-900' : 'text-violet-300 group-hover:text-white' }}" />
    @endif
    <span class="whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $slot }}</span>
</a>
