@extends('layouts.admin')

@use('Illuminate\Support\Facades\Storage')

@section('title', 'Deposit Details')

@section('content')
    @php
        $statusColor = match (strtolower($deposit->status)) {
            'approved' => 'text-gray-900',
            'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };
    @endphp

    <div class="mx-auto max-w-5xl">
        <!-- Page Header -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-xl font-semibold tracking-tight text-gray-900">Deposit Details</h2>
                <p class="mt-1 text-sm text-gray-500">Deposit #{{ $deposit->id }}</p>
            </div>

            <a href="{{ route('admin.deposits.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                Back to Deposits
            </a>
        </div>

        <!-- Deposit Information -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Deposit Information</h3>

            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-gray-500">Deposit ID</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">#{{ $deposit->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Method</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Asset</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->asset }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Amount</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Status</dt>
                    <dd class="mt-0.5 text-sm font-medium {{ $statusColor }}">{{ ucfirst($deposit->status) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Submitted At</dt>
                    <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->created_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <!-- User Information -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">User Information</h3>

            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div class="min-w-0">
                    <dt class="text-xs text-gray-500">Nickname</dt>
                    <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $deposit->user->name }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs text-gray-500">Email</dt>
                    <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $deposit->user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">User ID</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">#{{ $deposit->user->id }}</dd>
                </div>
            </dl>

            <div class="mt-4 border-t border-gray-100 pt-4">
                <a href="{{ route('admin.users.show', $deposit->user) }}" class="text-sm font-medium text-violet-600">View User</a>
            </div>
        </div>

        <!-- Payment Proof -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Payment Proof</h3>

            @if ($deposit->proof_image)
                <img
                    src="{{ Storage::disk('public')->url($deposit->proof_image) }}"
                    alt="Proof of deposit #{{ $deposit->id }}"
                    class="mt-4 max-h-[60vh] w-full max-w-md rounded border border-gray-200 object-contain"
                >
            @else
                <p class="mt-4 text-sm text-gray-500">No payment proof available.</p>
            @endif
        </div>

        <!-- Review Information -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Review Information</h3>

            @if ($deposit->reviewed_by)
                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500">Reviewed By</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->reviewer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Reviewed At</dt>
                        <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->reviewed_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>

                @if (strtolower($deposit->status) === 'rejected' && $deposit->rejection_reason)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <dt class="text-xs text-gray-500">Rejection Reason</dt>
                        <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->rejection_reason }}</dd>
                    </div>
                @endif
            @else
                <p class="mt-4 text-sm text-gray-500">Not reviewed yet.</p>
            @endif
        </div>
    </div>
@endsection
