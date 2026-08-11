@extends('layouts.admin')

@section('title', 'User Detail')

@section('content')
    @php
        $accountColor = match ($user->account_status) {
            'frozen' => 'text-red-600',
            default => 'text-gray-900',
        };

        $kycColor = match ($user->kyc_status) {
            'verified' => 'text-gray-900',
            'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };

        $tradeStatusColor = fn ($status) => match (strtolower($status)) {
            'completed' => 'text-gray-900',
            'cancelled', 'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };

        $financialStatusColor = fn ($status) => match (strtolower($status)) {
            'approved' => 'text-gray-900',
            'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };
    @endphp

    <div class="mx-auto max-w-7xl">
        <div
            x-data="{ loading: false }"
            @click="if ($event.target.closest('a')) loading = true"
            @submit="loading = true"
        >
            <!-- Page Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">User Detail</h2>
                    <p class="mt-1 truncate text-sm text-gray-500">{{ $user->name }} &middot; {{ $user->email }}</p>
                </div>

                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                    Back to Users
                </a>
            </div>

            @if (session('status') === 'account-frozen')
                <x-auth-session-status class="mb-4" status="Account successfully frozen." />
            @elseif (session('status') === 'account-unfrozen')
                <x-auth-session-status class="mb-4" status="Account successfully unfrozen." />
            @elseif (session('status') === 'account-already-frozen')
                <x-auth-session-status class="mb-4" status="This account is already frozen." />
            @elseif (session('status') === 'account-already-active')
                <x-auth-session-status class="mb-4" status="This account is already active." />
            @elseif (session('status') === 'password-reset')
                <x-auth-session-status class="mb-4" status="Login password successfully reset." />
            @elseif (session('status') === 'withdrawal-password-reset')
                <x-auth-session-status class="mb-4" status="Withdrawal password successfully reset." />
            @elseif (session('status') === 'balance-added')
                <x-auth-session-status class="mb-4" status="Balance successfully added to the wallet." />
            @elseif (session('status') === 'balance-deducted')
                <x-auth-session-status class="mb-4" status="Balance successfully deducted from the wallet." />
            @endif

            @if (session('error') === 'account-freeze-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to freeze this account.</p>
            @elseif (session('error') === 'account-unfreeze-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to unfreeze this account.</p>
            @elseif (session('error') === 'password-reset-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to reset login password.</p>
            @elseif (session('error') === 'withdrawal-password-reset-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to reset withdrawal password.</p>
            @elseif (session('error') === 'balance-add-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to add balance.</p>
            @elseif (session('error') === 'balance-deduct-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Unable to deduct balance.</p>
            @elseif (session('error') === 'invalid-adjustment-action')
                <p class="mb-4 text-sm font-medium text-red-600">Invalid balance adjustment request.</p>
            @endif

            <!-- User Information -->
            <div class="rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900">User Information</h3>

                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div class="min-w-0">
                        <dt class="text-xs text-gray-500">Nickname</dt>
                        <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $user->name }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-gray-500">Email</dt>
                        <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs text-gray-500">Phone</dt>
                        <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $user->phone ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Account Status + Registration Information -->
            <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-900">Account Status</h3>

                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs text-gray-500">Account Status</dt>
                            <dd class="mt-0.5 text-sm font-medium {{ $accountColor }}">{{ ucfirst($user->account_status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">KYC Status</dt>
                            <dd class="mt-0.5 text-sm font-medium {{ $kycColor }}">{{ ucfirst($user->kyc_status) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-900">Registration Information</h3>

                    <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs text-gray-500">Registration Date</dt>
                            <dd class="mt-0.5 text-sm text-gray-700">{{ $user->created_at->format('d M Y') }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs text-gray-500">Referral Code</dt>
                            <dd class="mt-0.5 truncate text-sm text-gray-700">{{ $user->referral_code ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Account Actions -->
            @if ($user->role === 'user')
                <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-900">Account Actions</h3>

                    <div class="mt-4 flex flex-wrap gap-3">
                        @if ($user->account_status === 'frozen')
                            <button
                                type="button"
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'unfreeze-account')"
                                class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white"
                            >
                                Unfreeze Account
                            </button>
                        @else
                            <button
                                type="button"
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'freeze-account')"
                                class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-600"
                            >
                                Freeze Account
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Security Actions -->
                <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
                    <h3 class="text-sm font-semibold text-gray-900">Security Actions</h3>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <button
                            type="button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'reset-login-password')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Reset Login Password
                        </button>
                        <button
                            type="button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'reset-withdrawal-password')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Reset Withdrawal Password
                        </button>
                    </div>
                </div>
            @endif

            <!-- Wallet / Assets -->
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900">Wallet / Assets</h3>

                @if ($user->wallets->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">No wallet assets found.</p>
                @else
                    <dl class="mt-4 divide-y divide-gray-100">
                        @foreach ($user->wallets as $wallet)
                            <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                <dt class="text-sm font-medium text-gray-900">{{ $wallet->asset }}</dt>
                                <dd class="truncate text-sm text-gray-700">{{ rtrim(rtrim($wallet->balance, '0'), '.') }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if ($user->role === 'user')
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <button
                                type="button"
                                x-data=""
                                x-on:click="$dispatch('open-modal', 'adjust-balance')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Adjust Balance
                            </button>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Trading History -->
            <div class="mt-4 rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Trading History</h3>
                </div>

                @if ($trades->isEmpty())
                    <p class="px-5 py-8 text-center text-sm text-gray-500">No trading history found.</p>
                @else
                    <!-- Table (md and up) -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Pair</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Side</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                    <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Price</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($trades as $trade)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ $trade->pair }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700">{{ ucfirst($trade->side) }}</td>
                                        <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($trade->amount, '0'), '.') }}</td>
                                        <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($trade->price, '0'), '.') }}</td>
                                        <td class="px-5 py-3 text-sm font-medium {{ $tradeStatusColor($trade->status) }}">{{ ucfirst($trade->status) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $trade->created_at->format('d M Y H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Card list (below md) -->
                    <div class="divide-y divide-gray-100 md:hidden">
                        @foreach ($trades as $trade)
                            <div class="px-5 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate text-sm font-medium text-gray-900">{{ $trade->pair }}</span>
                                    <span class="text-sm text-gray-700">{{ ucfirst($trade->side) }}</span>
                                </div>
                                <dl class="mt-2 space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Amount</dt>
                                        <dd class="text-sm text-gray-700">{{ rtrim(rtrim($trade->amount, '0'), '.') }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Price</dt>
                                        <dd class="text-sm text-gray-700">{{ rtrim(rtrim($trade->price, '0'), '.') }}</dd>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <dt class="text-xs text-gray-500">Status</dt>
                                        <dd class="text-sm font-medium {{ $tradeStatusColor($trade->status) }}">{{ ucfirst($trade->status) }}</dd>
                                    </div>
                                </dl>
                                <p class="mt-2 text-xs text-gray-400">{{ $trade->created_at->format('d M Y H:i') }}</p>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $trades->links() }}
                    </div>
                @endif
            </div>

            <!-- Financial History -->
            <div class="mt-6">
                <h2 class="text-base font-semibold text-gray-900">Financial History</h2>

                <!-- Deposit History -->
                <div class="mt-3 rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">Deposit History</h3>
                    </div>

                    @if ($deposits->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-gray-500">No deposit history found.</p>
                    @else
                        <!-- Table (md and up) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Method</th>
                                        <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($deposits as $deposit)
                                        <tr class="border-b border-gray-100 last:border-0">
                                            <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ ucfirst($deposit->method) }}</td>
                                            <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</td>
                                            <td class="px-5 py-3 text-sm font-medium {{ $financialStatusColor($deposit->status) }}">{{ ucfirst($deposit->status) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $deposit->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Card list (below md) -->
                        <div class="divide-y divide-gray-100 md:hidden">
                            @foreach ($deposits as $deposit)
                                <div class="px-5 py-4">
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst($deposit->method) }}</span>
                                    <dl class="mt-2 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <dt class="text-xs text-gray-500">Amount</dt>
                                            <dd class="text-sm text-gray-700">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-xs text-gray-500">Status</dt>
                                            <dd class="text-sm font-medium {{ $financialStatusColor($deposit->status) }}">{{ ucfirst($deposit->status) }}</dd>
                                        </div>
                                    </dl>
                                    <p class="mt-2 text-xs text-gray-400">{{ $deposit->created_at->format('d M Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 px-5 py-4">
                            {{ $deposits->links() }}
                        </div>
                    @endif
                </div>

                <!-- Withdrawal History -->
                <div class="mt-4 rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h3 class="text-sm font-semibold text-gray-900">Withdrawal History</h3>
                    </div>

                    @if ($withdrawals->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-gray-500">No withdrawal history found.</p>
                    @else
                        <!-- Table (md and up) -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-gray-100">
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Method</th>
                                        <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Destination</th>
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                        <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($withdrawals as $withdrawal)
                                        <tr class="border-b border-gray-100 last:border-0">
                                            <td class="px-5 py-3 text-sm font-medium text-gray-900">{{ ucfirst($withdrawal->method) }}</td>
                                            <td class="px-5 py-3 text-right text-sm text-gray-700">{{ rtrim(rtrim($withdrawal->amount, '0'), '.') }} {{ $withdrawal->asset }}</td>
                                            <td class="max-w-[160px] truncate px-5 py-3 text-sm text-gray-700" title="{{ $withdrawal->destination }}">{{ $withdrawal->destination }}</td>
                                            <td class="px-5 py-3 text-sm font-medium {{ $financialStatusColor($withdrawal->status) }}">{{ ucfirst($withdrawal->status) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $withdrawal->created_at->format('d M Y H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Card list (below md) -->
                        <div class="divide-y divide-gray-100 md:hidden">
                            @foreach ($withdrawals as $withdrawal)
                                <div class="px-5 py-4">
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst($withdrawal->method) }}</span>
                                    <dl class="mt-2 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <dt class="text-xs text-gray-500">Amount</dt>
                                            <dd class="text-sm text-gray-700">{{ rtrim(rtrim($withdrawal->amount, '0'), '.') }} {{ $withdrawal->asset }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <dt class="shrink-0 text-xs text-gray-500">Destination</dt>
                                            <dd class="truncate text-sm text-gray-700" title="{{ $withdrawal->destination }}">{{ $withdrawal->destination }}</dd>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <dt class="text-xs text-gray-500">Status</dt>
                                            <dd class="text-sm font-medium {{ $financialStatusColor($withdrawal->status) }}">{{ ucfirst($withdrawal->status) }}</dd>
                                        </div>
                                    </dl>
                                    <p class="mt-2 text-xs text-gray-400">{{ $withdrawal->created_at->format('d M Y H:i') }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 px-5 py-4">
                            {{ $withdrawals->links() }}
                        </div>
                    @endif
                </div>
            </div>

            @if ($user->role === 'user')
                <x-modal name="freeze-account" focusable>
                    <form method="post" action="{{ route('admin.users.freeze', $user) }}" class="p-6">
                        @csrf

                        <h2 class="text-base font-semibold text-gray-900">Freeze Account</h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Are you sure you want to freeze this account? The user will no longer be able to access the user area until the account is unfrozen.
                        </p>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-600 disabled:opacity-50"
                            >
                                Freeze Account
                            </button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="unfreeze-account" focusable>
                    <form method="post" action="{{ route('admin.users.unfreeze', $user) }}" class="p-6">
                        @csrf

                        <h2 class="text-base font-semibold text-gray-900">Unfreeze Account</h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Are you sure you want to unfreeze this account? The user will be able to access the user area again.
                        </p>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                Unfreeze Account
                            </button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="reset-login-password" :show="$errors->resetLoginPassword->isNotEmpty()" focusable>
                    <form
                        method="post"
                        action="{{ route('admin.users.reset-password', $user) }}"
                        class="p-6"
                        x-effect="! show && $el.reset()"
                    >
                        @csrf

                        <h2 class="text-base font-semibold text-gray-900">Reset Login Password</h2>

                        <p class="mt-1 text-sm text-gray-600">User: {{ $user->email }}</p>

                        <div class="mt-4">
                            <x-input-label for="reset_password_password" value="New Password" />
                            <x-password-input id="reset_password_password" name="password" class="mt-1" autocomplete="new-password" />
                            <x-input-error :messages="$errors->resetLoginPassword->get('password')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="reset_password_password_confirmation" value="Confirm Password" />
                            <x-password-input id="reset_password_password_confirmation" name="password_confirmation" class="mt-1" autocomplete="new-password" />
                        </div>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                Reset Password
                            </button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="reset-withdrawal-password" :show="$errors->resetWithdrawalPassword->isNotEmpty()" focusable>
                    <form
                        method="post"
                        action="{{ route('admin.users.reset-withdrawal-password', $user) }}"
                        class="p-6"
                        x-effect="! show && $el.reset()"
                    >
                        @csrf

                        <h2 class="text-base font-semibold text-gray-900">Reset Withdrawal Password</h2>

                        <p class="mt-1 text-sm text-gray-600">User: {{ $user->email }}</p>

                        <div class="mt-4">
                            <x-input-label for="reset_withdrawal_password" value="New Withdrawal Password" />
                            <x-password-input
                                id="reset_withdrawal_password"
                                name="withdrawal_password"
                                class="mt-1"
                                autocomplete="off"
                                inputmode="numeric"
                                maxlength="6"
                            />
                            <x-input-error :messages="$errors->resetWithdrawalPassword->get('withdrawal_password')" class="mt-2" />
                        </div>

                        <div class="mt-4">
                            <x-input-label for="reset_withdrawal_password_confirmation" value="Confirm Withdrawal Password" />
                            <x-password-input
                                id="reset_withdrawal_password_confirmation"
                                name="withdrawal_password_confirmation"
                                class="mt-1"
                                autocomplete="off"
                                inputmode="numeric"
                                maxlength="6"
                            />
                        </div>

                        <p class="mt-2 text-xs text-gray-500">Password must contain exactly 6 numeric digits.</p>

                        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button
                                type="button"
                                x-on:click="$dispatch('close')"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="loading"
                                class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                Reset Password
                            </button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="adjust-balance" :show="$errors->adjustBalance->isNotEmpty()" focusable>
                    <div
                        class="p-6"
                        x-data="{
                            step: 'form',
                            asset: @js(old('asset', '')),
                            adjustmentType: @js(old('action', 'add')),
                            amount: @js(old('amount', '')),
                            reason: @js(old('reason', '')),
                            errors: {
                                asset: @js($errors->adjustBalance->first('asset') ?: ''),
                                amount: @js($errors->adjustBalance->first('amount') ?: ''),
                                reason: @js($errors->adjustBalance->first('reason') ?: ''),
                            },
                            reset() {
                                this.step = 'form';
                                this.asset = '';
                                this.adjustmentType = 'add';
                                this.amount = '';
                                this.reason = '';
                                this.errors = { asset: '', amount: '', reason: '' };
                            },
                            validate() {
                                this.errors = { asset: '', amount: '', reason: '' };
                                let valid = true;

                                if (! this.asset) {
                                    this.errors.asset = 'Asset is required.';
                                    valid = false;
                                }

                                if (this.amount === '' || this.amount === null) {
                                    this.errors.amount = 'Amount is required.';
                                    valid = false;
                                } else if (isNaN(this.amount) || parseFloat(this.amount) <= 0) {
                                    this.errors.amount = 'Amount must be greater than 0.';
                                    valid = false;
                                }

                                if (! this.reason.trim()) {
                                    this.errors.reason = 'Reason is required.';
                                    valid = false;
                                }

                                return valid;
                            },
                            continueToConfirm() {
                                if (this.validate()) {
                                    this.step = 'confirm';
                                }
                            },
                        }"
                        x-effect="! show && reset()"
                    >
                        <!-- Step 1: Form -->
                        <form x-show="step === 'form'" x-on:submit.prevent.stop="continueToConfirm()">
                            <h2 class="text-base font-semibold text-gray-900">Adjust Balance</h2>

                            <p class="mt-1 text-sm text-gray-600">User: {{ $user->email }}</p>

                            <div class="mt-4">
                                <x-input-label for="adjust_balance_asset" value="Asset" />
                                <select
                                    id="adjust_balance_asset"
                                    x-model="asset"
                                    class="mt-1 block w-full rounded-lg border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                    <option value="">Select an asset</option>
                                    @foreach ($user->wallets as $wallet)
                                        <option value="{{ $wallet->asset }}">{{ $wallet->asset }}</option>
                                    @endforeach
                                </select>
                                <p x-show="errors.asset" x-text="errors.asset" class="mt-2 text-sm text-red-600" style="display: none;"></p>
                            </div>

                            <div class="mt-4">
                                <span class="block font-medium text-sm text-gray-700">Adjustment</span>
                                <div class="mt-2 space-y-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="adjustment_type" value="add" x-model="adjustmentType" class="border-gray-300 text-violet-600 focus:ring-violet-500">
                                        Add Balance
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="adjustment_type" value="deduct" x-model="adjustmentType" class="border-gray-300 text-violet-600 focus:ring-violet-500">
                                        Deduct Balance
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="adjust_balance_amount" value="Amount" />
                                <input
                                    type="text"
                                    id="adjust_balance_amount"
                                    x-model="amount"
                                    inputmode="decimal"
                                    autocomplete="off"
                                    class="mt-1 block w-full rounded-lg border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                <p x-show="errors.amount" x-text="errors.amount" class="mt-2 text-sm text-red-600" style="display: none;"></p>
                            </div>

                            <div class="mt-4">
                                <x-input-label for="adjust_balance_reason" value="Reason" />
                                <textarea
                                    id="adjust_balance_reason"
                                    x-model="reason"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                ></textarea>
                                <p x-show="errors.reason" x-text="errors.reason" class="mt-2 text-sm text-red-600" style="display: none;"></p>
                            </div>

                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    x-on:click="$dispatch('close')"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white"
                                >
                                    Continue
                                </button>
                            </div>
                        </form>

                        <!-- Step 2: Confirmation -->
                        <div x-show="step === 'confirm'" style="display: none;">
                            <h2 class="text-base font-semibold text-gray-900">Confirm Balance Adjustment</h2>

                            <dl class="mt-4 space-y-3">
                                <div>
                                    <dt class="text-xs text-gray-500">User</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $user->email }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Asset</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-gray-900" x-text="asset"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Action</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-gray-900" x-text="adjustmentType === 'add' ? 'Add Balance' : 'Deduct Balance'"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Amount</dt>
                                    <dd class="mt-0.5 text-sm font-medium text-gray-900" x-text="amount + ' ' + asset"></dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500">Reason</dt>
                                    <dd class="mt-0.5 text-sm text-gray-700" x-text="reason"></dd>
                                </div>
                            </dl>

                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button
                                    type="button"
                                    x-on:click="step = 'form'"
                                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                                >
                                    Back
                                </button>

                                <form method="POST" action="{{ route('admin.users.adjust-balance', $user) }}">
                                    @csrf
                                    <input type="hidden" name="action" :value="adjustmentType">
                                    <input type="hidden" name="asset" :value="asset">
                                    <input type="hidden" name="amount" :value="amount">
                                    <input type="hidden" name="reason" :value="reason">
                                    <button
                                        type="submit"
                                        :disabled="loading"
                                        class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                                    >
                                        Confirm
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </x-modal>
            @endif

            <!-- Global full-page loading overlay: same mechanism as admin/users/index.blade.php -->
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
