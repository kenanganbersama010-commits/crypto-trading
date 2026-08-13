@extends('layouts.admin')

@section('title', 'Detail Deposit')

@section('content')
    @php
        $statusColor = match (strtolower($deposit->status)) {
            'approved' => 'text-gray-900',
            'rejected' => 'text-red-600',
            default => 'text-gray-500',
        };
        $statusLabel = match (strtolower($deposit->status)) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Menunggu',
        };
    @endphp

    <div
        x-data="{ loading: false }"
        @submit="loading = true"
        @click="if ($event.target.closest('a')) loading = true"
    >
        <div class="mx-auto max-w-5xl">
            <!-- Page Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold tracking-tight text-gray-900">Detail Deposit</h2>
                    <p class="mt-1 text-sm text-gray-500">Deposit #{{ $deposit->id }}</p>
                </div>

                <a href="{{ route('admin.deposits.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                    Kembali ke Deposit
                </a>
            </div>

            @if (session('status') === 'deposit-approved')
                <x-auth-session-status class="mb-4" status="Deposit berhasil disetujui." />
            @elseif (session('status') === 'deposit-rejected')
                <x-auth-session-status class="mb-4" status="Deposit berhasil ditolak." />
            @endif

            @if (session('error') === 'deposit-already-processed')
                <p class="mb-4 text-sm font-medium text-red-600">Deposit ini sudah diproses sebelumnya.</p>
            @elseif (session('error') === 'deposit-approve-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Gagal menyetujui deposit.</p>
            @elseif (session('error') === 'deposit-reject-failed')
                <p class="mb-4 text-sm font-medium text-red-600">Gagal menolak deposit.</p>
            @endif

        <!-- Deposit Information -->
        <div class="rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Informasi Deposit</h3>

            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div>
                    <dt class="text-xs text-gray-500">ID Deposit</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">#{{ $deposit->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Metode</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Aset</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->asset }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Nominal</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Status</dt>
                    <dd class="mt-0.5 text-sm font-medium {{ $statusColor }}">{{ $statusLabel }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Diajukan Pada</dt>
                    <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->created_at->format('d M Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        @if (strtolower($deposit->status) === 'pending')
            <!-- Actions -->
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
                <h3 class="text-sm font-semibold text-gray-900">Aksi</h3>

                <div class="mt-4 flex flex-wrap gap-3">
                    <button
                        type="button"
                        x-data=""
                        x-on:click="$dispatch('open-modal', 'approve-deposit')"
                        class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white"
                    >
                        Setujui Deposit
                    </button>
                    <button
                        type="button"
                        x-data=""
                        x-on:click="$dispatch('open-modal', 'reject-deposit')"
                        class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-600"
                    >
                        Tolak Deposit
                    </button>
                </div>
            </div>
        @endif

        <!-- User Information -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Informasi Pengguna</h3>

            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div class="min-w-0">
                    <dt class="text-xs text-gray-500">Nama</dt>
                    <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $deposit->user->name }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs text-gray-500">Email</dt>
                    <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $deposit->user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">ID Pengguna</dt>
                    <dd class="mt-0.5 text-sm font-medium text-gray-900">#{{ $deposit->user->id }}</dd>
                </div>
            </dl>

            <div class="mt-4 border-t border-gray-100 pt-4">
                <a href="{{ route('admin.users.show', $deposit->user) }}" class="text-sm font-medium text-violet-600">Lihat Pengguna</a>
            </div>
        </div>

        <!-- Payment Proof -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Bukti Pembayaran</h3>

            @if ($deposit->proof_image_url)
                <img
                    src="{{ $deposit->proof_image_url }}"
                    alt="Bukti deposit #{{ $deposit->id }}"
                    class="mt-4 max-h-[60vh] w-full max-w-md rounded border border-gray-200 object-contain"
                >
            @else
                <p class="mt-4 text-sm text-gray-500">Bukti pembayaran tidak tersedia.</p>
            @endif
        </div>

        <!-- Review Information -->
        <div class="mt-4 rounded-lg border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900">Informasi Peninjauan</h3>

            @if ($deposit->reviewed_by)
                <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500">Ditinjau Oleh</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->reviewer->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Ditinjau Pada</dt>
                        <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->reviewed_at->format('d M Y H:i') }}</dd>
                    </div>
                </dl>

                @if (strtolower($deposit->status) === 'rejected' && $deposit->rejection_reason)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <dt class="text-xs text-gray-500">Alasan Penolakan</dt>
                        <dd class="mt-0.5 text-sm text-gray-700">{{ $deposit->rejection_reason }}</dd>
                    </div>
                @endif
            @else
                <p class="mt-4 text-sm text-gray-500">Belum ditinjau.</p>
            @endif
        </div>
        </div>

        @if (strtolower($deposit->status) === 'pending')
            <x-modal name="approve-deposit" focusable>
                <form method="post" action="{{ route('admin.deposits.approve', $deposit) }}" class="p-6">
                    @csrf

                    <h2 class="text-base font-semibold text-gray-900">Setujui Deposit</h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Apakah Anda yakin ingin menyetujui deposit ini? Nominal akan ditambahkan ke wallet pengguna.
                    </p>

                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-xs text-gray-500">Pengguna</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Metode</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Aset</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->asset }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Nominal</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            <span x-show="!loading">Setujui Deposit</span>
                            <span x-show="loading" style="display: none;">Menyetujui...</span>
                        </button>
                    </div>
                </form>
            </x-modal>

            <x-modal name="reject-deposit" :show="$errors->rejectDeposit->isNotEmpty()" focusable>
                <form
                    method="post"
                    action="{{ route('admin.deposits.reject', $deposit) }}"
                    class="p-6"
                    x-data="{ reason: @js(old('rejection_reason', '')) }"
                    x-effect="! show && (reason = '')"
                >
                    @csrf

                    <h2 class="text-base font-semibold text-gray-900">Tolak Deposit</h2>

                    <p class="mt-2 text-sm text-gray-600">
                        Apakah Anda yakin ingin menolak deposit ini? Nominal tidak akan ditambahkan ke wallet pengguna.
                    </p>

                    <dl class="mt-4 space-y-3">
                        <div>
                            <dt class="text-xs text-gray-500">Pengguna</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Metode</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ ucwords(str_replace('_', ' ', $deposit->method)) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Aset</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $deposit->asset }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500">Nominal</dt>
                            <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ rtrim(rtrim($deposit->amount, '0'), '.') }} {{ $deposit->asset }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4">
                        <x-input-label for="rejection_reason" value="Alasan Penolakan" />
                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            x-model="reason"
                            rows="3"
                            maxlength="500"
                            placeholder="Bukti pembayaran tidak valid."
                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        ></textarea>
                        <x-input-error :messages="$errors->rejectDeposit->get('rejection_reason')" class="mt-2" />
                    </div>

                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            x-on:click="$dispatch('close')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="loading || ! reason.trim()"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            <span x-show="!loading">Tolak Deposit</span>
                            <span x-show="loading" style="display: none;">Menolak...</span>
                        </button>
                    </div>
                </form>
            </x-modal>
        @endif

        <!-- Global full-page loading overlay: same mechanism as admin/users/show.blade.php. -->
        <div
            x-show="loading"
            style="display: none;"
            class="fixed inset-0 z-[60] flex items-center justify-center gap-2 bg-white/60 backdrop-blur-sm"
            role="status"
            aria-live="polite"
            aria-label="Memuat"
        >
            <div class="h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-violet-600"></div>
            <span class="text-sm text-gray-600">Memuat...</span>
        </div>
    </div>
@endsection
