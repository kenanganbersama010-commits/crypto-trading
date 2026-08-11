@props(['status'])

@php
    $classes = match (strtolower($status)) {
        'completed' => 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20',
        'pending' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20',
        'failed' => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20',
        default => 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10',
    };
@endphp

<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $classes }}">
    {{ $status }}
</span>
