@extends('layouts.admin')

@section('title', 'Deposits')

@section('content')
    @php
        $statusColor = fn ($status) => match (strtolower($status)) {
            'approved' => 'text-gray-900',
            'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };
    @endphp

    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Deposits</h2>
            <p class="mt-1 text-sm text-gray-500">Review and monitor user deposit submissions.</p>
        </div>

        <div
            x-data="{ loading: false }"
            @click="if ($event.target.closest('a')) loading = true"
        >
            <div class="rounded-lg border border-gray-200 bg-white">
                @if ($deposits->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No deposits found.</p>
                @else
                    <!-- Table (md and up) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">User</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Method</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Asset</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Submitted</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Proof</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deposits as $deposit)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-900">
                                            {{ $deposit->user->name }}
                                            <span class="block text-xs font-normal text-gray-500">{{ $deposit->user->email }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700">{{ $deposit->asset }}</td>
                                        <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($deposit->amount, '0'), '.') }}</td>
                                        <td class="px-5 py-3 text-sm font-medium {{ $statusColor($deposit->status) }}">{{ ucfirst($deposit->status) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $deposit->created_at->format('d M Y H:i') }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700">
                                            @if ($deposit->proof_image)
                                                <button
                                                    type="button"
                                                    x-data=""
                                                    x-on:click="$dispatch('open-modal', 'deposit-proof-{{ $deposit->id }}')"
                                                >
                                                    <img
                                                        src="{{ $deposit->proof_image_url }}"
                                                        alt="Proof of deposit #{{ $deposit->id }}"
                                                        class="h-10 w-10 rounded border border-gray-200 object-cover"
                                                    >
                                                </button>
                                            @else
                                                <span class="text-gray-400">No proof</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-sm">
                                            <a href="{{ route('admin.deposits.show', $deposit) }}" class="font-medium text-violet-600">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Card list (below md) -->
                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach ($deposits as $deposit)
                            <div class="px-5 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate text-sm font-medium text-gray-900">{{ $deposit->user->name }}</span>
                                    <span class="text-sm font-medium {{ $statusColor($deposit->status) }}">{{ ucfirst($deposit->status) }}</span>
                                </div>
                                <dl class="mt-2 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Method</dt>
                                        <dd class="text-sm text-gray-700">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Asset</dt>
                                        <dd class="text-sm text-gray-700">{{ $deposit->asset }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Amount</dt>
                                        <dd class="text-sm text-gray-700">{{ rtrim(rtrim($deposit->amount, '0'), '.') }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Submitted</dt>
                                        <dd class="text-sm text-gray-500">{{ $deposit->created_at->format('d M Y H:i') }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Proof</dt>
                                        <dd class="text-sm text-gray-700">
                                            @if ($deposit->proof_image)
                                                <button
                                                    type="button"
                                                    x-data=""
                                                    x-on:click="$dispatch('open-modal', 'deposit-proof-{{ $deposit->id }}')"
                                                >
                                                    <img
                                                        src="{{ $deposit->proof_image_url }}"
                                                        alt="Proof of deposit #{{ $deposit->id }}"
                                                        class="h-10 w-10 rounded border border-gray-200 object-cover"
                                                    >
                                                </button>
                                            @else
                                                <span class="text-gray-400">No proof</span>
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                                <div class="mt-3 border-t border-gray-100 pt-3">
                                    <a href="{{ route('admin.deposits.show', $deposit) }}" class="text-sm font-medium text-violet-600">View</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @foreach ($deposits as $deposit)
                @if ($deposit->proof_image)
                    <x-modal :name="'deposit-proof-'.$deposit->id" max-width="lg">
                        <div class="p-6">
                            <h2 class="text-base font-semibold text-gray-900">Proof of Deposit</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $deposit->user->name }} &middot; {{ $deposit->asset }} {{ rtrim(rtrim($deposit->amount, '0'), '.') }}</p>

                            <img
                                src="{{ $deposit->proof_image_url }}"
                                alt="Proof of deposit #{{ $deposit->id }}"
                                class="mt-4 max-h-[70vh] w-full rounded border border-gray-200 object-contain"
                            >

                            <div class="mt-6 flex justify-end">
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </x-modal>
                @endif
            @endforeach

            <!--
                Global full-page loading overlay: same mechanism as admin/users/index.blade.php,
                admin/users/show.blade.php, and admin/adjustment-history/index.blade.php.
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
