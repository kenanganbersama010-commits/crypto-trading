@props(['href' => '#', 'icon' => null, 'active' => false])

<a
    href="{{ $href }}"
    @click="sidebarOpen = false"
    @if ($active) aria-current="page" @endif
    {{ $attributes->merge(['class' =>
        'group flex items-center gap-3 rounded-md border-l-2 py-2 pl-2.5 pr-3 text-sm font-medium transition-colors duration-100 '
        . ($active
            ? 'border-violet-500 bg-slate-800 text-white'
            : 'border-transparent text-slate-300 hover:bg-slate-800/60 hover:text-white')
    ]) }}
>
    @if ($icon)
        <x-icon :name="$icon" class="h-5 w-5 shrink-0 {{ $active ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" />
    @endif
    <span>{{ $slot }}</span>
</a>
