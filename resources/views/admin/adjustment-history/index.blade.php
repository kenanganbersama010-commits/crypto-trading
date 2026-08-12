@extends('layouts.admin')

@section('title', 'Adjustment History')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Adjustment History</h2>
            <p class="mt-1 text-sm text-gray-500">Review all balance adjustments performed by administrators.</p>
        </div>

        <div
            x-data="{ loading: false }"
            @submit="loading = true"
            @click="if ($event.target.closest('a')) loading = true"
        >
            <!-- Summary -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-stat-card label="Total Adjustments" :value="$totalCount" />
                <x-stat-card label="Add Balance" :value="$addCount" value-class="text-emerald-600" />
                <x-stat-card label="Deduct Balance" :value="$deductCount" value-class="text-red-600" />
            </div>

            <!-- Filter Card -->
            <form method="GET" action="{{ route('admin.adjustment-history.index') }}" class="mt-4 rounded-lg border border-gray-200 bg-white p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-xs font-medium text-gray-500">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Search user..."
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                    <div>
                        <label for="asset" class="block text-xs font-medium text-gray-500">Asset</label>
                        <select
                            id="asset"
                            name="asset"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                            <option value="">All</option>
                            @foreach ($assets as $assetOption)
                                <option value="{{ $assetOption }}" @selected(request('asset') === $assetOption)>{{ $assetOption }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-xs font-medium text-gray-500">Type</label>
                        <select
                            id="type"
                            name="type"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                            <option value="">All</option>
                            <option value="add" @selected(request('type') === 'add')>Add</option>
                            <option value="deduct" @selected(request('type') === 'deduct')>Deduct</option>
                        </select>
                    </div>

                    <div>
                        <label for="from_date" class="block text-xs font-medium text-gray-500">From Date</label>
                        <input
                            type="date"
                            id="from_date"
                            name="from_date"
                            value="{{ request('from_date') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                    <div>
                        <label for="to_date" class="block text-xs font-medium text-gray-500">To Date</label>
                        <input
                            type="date"
                            id="to_date"
                            name="to_date"
                            value="{{ request('to_date') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                </div>

                @if ($dateFilterInvalid)
                    <p class="mt-2 text-sm text-red-600">Invalid date filter. Please use a valid date and make sure From date is not after To date. The date filter was not applied.</p>
                @endif

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white">
                        Apply Filters
                    </button>

                    @if (request()->hasAny(['search', 'asset', 'type', 'from_date', 'to_date']))
                        <a href="{{ route('admin.adjustment-history.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <!-- History List -->
            <div class="mt-4 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">All Adjustments</h3>
                </div>

                @if ($adjustments->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-gray-500">
                        @if (request()->hasAny(['search', 'asset', 'type', 'from_date', 'to_date']))
                            No adjustment history found for the selected filters.
                        @else
                            No adjustment history found.
                        @endif
                    </p>
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

                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $adjustments->links() }}
                    </div>
                @endif
            </div>

            <!--
                Global full-page loading overlay: same mechanism as admin/users/index.blade.php
                and admin/users/show.blade.php. fixed inset-0 (not absolute) so it covers the
                sidebar/topbar too; z-[60] sits above every existing layer in the app.
            -->
            <div
                x-show="loading"
                style="display: none;"
                class="fixed inset-0 z-[60] flex items-center justify-center gap-2 bg-white/60 backdrop-blur-sm"
                role="status"
                aria-live="polite"
                aria-label="Loading"
            >
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-violet-600"></div>
                <span class="text-sm text-gray-600">Loading...</span>
            </div>
        </div>
    </div>
@endsection
