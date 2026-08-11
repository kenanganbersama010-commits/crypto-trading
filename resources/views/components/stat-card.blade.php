@props(['label', 'value', 'valueClass' => 'text-gray-900'])

<div class="rounded-lg border border-gray-200 bg-white p-5">
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p class="mt-2 text-2xl font-semibold tracking-tight {{ $valueClass }}">{{ $value }}</p>
</div>
