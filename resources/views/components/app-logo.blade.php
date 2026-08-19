@props(['label' => null])

<div class="flex items-center gap-2.5">
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 shadow-sm shadow-blue-900/40">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-5 w-5 text-white">
            <path d="M12 2L3 7v10l9 5 9-5V7l-9-5z" fill="currentColor" fill-opacity="0.25" />
            <path d="M12 2v20M3 7l9 5 9-5M3 17l9-5 9 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </span>
    <span class="text-base font-semibold tracking-tight text-white truncate">
        {{ \App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading') }}
    </span>
    @if ($label)
        <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400 shrink-0">{{ $label }}</span>
    @endif
</div>
