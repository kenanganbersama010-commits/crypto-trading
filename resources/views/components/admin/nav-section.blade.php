@props(['title'])

<p class="px-3 mb-2 whitespace-nowrap text-[11px] font-semibold uppercase tracking-wider text-violet-300" :class="sidebarCollapsed ? 'lg:hidden' : ''">
    {{ $title }}
</p>
