@extends('layouts.admin')

@section('title', 'Adjustment History')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Adjustment History</h2>
            <p class="mt-1 text-sm text-gray-500">Review all balance adjustments performed by administrators.</p>
        </div>

        <!-- Summary -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <x-stat-card label="Total Adjustments" :value="$adjustments->count()" />
            <x-stat-card label="Add Balance" :value="$adjustments->where('type', 'add')->count()" value-class="text-emerald-600" />
            <x-stat-card label="Deduct Balance" :value="$adjustments->where('type', 'deduct')->count()" value-class="text-red-600" />
        </div>

        <!-- History List -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="text-sm font-semibold text-gray-900">All Adjustments</h3>
            </div>

            @if ($adjustments->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-gray-500">No adjustment history found.</p>
            @else
                <!-- Table (md and up) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date & Time</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">User</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Asset</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Type</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Before</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">After</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Admin</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($adjustments as $adjustment)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-5 py-3 text-sm text-gray-500">{{ $adjustment->created_at->format('d M Y H:i') }}</td>
                                    <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $adjustment->user->name }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-700">{{ $adjustment->asset }}</td>
                                    <td class="px-5 py-3 text-sm font-medium {{ $adjustment->type === 'add' ? 'text-emerald-600' : 'text-red-600' }}">{{ ucfirst($adjustment->type) }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($adjustment->amount, '0'), '.') }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($adjustment->balance_before, '0'), '.') }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($adjustment->balance_after, '0'), '.') }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-700">{{ $adjustment->admin->name }}</td>
                                    <td class="max-w-[200px] truncate px-5 py-3 text-sm text-gray-700" title="{{ $adjustment->reason }}">{{ $adjustment->reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Card list (below md) -->
                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach ($adjustments as $adjustment)
                        <div class="px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="truncate text-sm font-medium text-gray-900">{{ $adjustment->user->name }}</span>
                                <span class="text-sm font-medium {{ $adjustment->type === 'add' ? 'text-emerald-600' : 'text-red-600' }}">{{ ucfirst($adjustment->type) }}</span>
                            </div>
                            <dl class="mt-2 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-gray-500">Asset</dt>
                                    <dd class="text-sm text-gray-700">{{ $adjustment->asset }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-gray-500">Amount</dt>
                                    <dd class="text-sm text-gray-700">{{ rtrim(rtrim($adjustment->amount, '0'), '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-gray-500">Balance Before</dt>
                                    <dd class="text-sm text-gray-700">{{ rtrim(rtrim($adjustment->balance_before, '0'), '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-gray-500">Balance After</dt>
                                    <dd class="text-sm text-gray-700">{{ rtrim(rtrim($adjustment->balance_after, '0'), '.') }}</dd>
                                </div>
                                <div class="flex items-center justify-between">
                                    <dt class="text-xs text-gray-500">Admin</dt>
                                    <dd class="text-sm text-gray-700">{{ $adjustment->admin->name }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="shrink-0 text-xs text-gray-500">Reason</dt>
                                    <dd class="truncate text-sm text-gray-700" title="{{ $adjustment->reason }}">{{ $adjustment->reason }}</dd>
                                </div>
                            </dl>
                            <p class="mt-2 text-xs text-gray-400">{{ $adjustment->created_at->format('d M Y H:i') }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
