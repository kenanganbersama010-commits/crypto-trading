@props(['user'])

@php
    $accountColor = match ($user->account_status) {
        'frozen' => 'text-red-600',
        default => 'text-gray-900',
    };

    $accountLabel = match ($user->account_status) {
        'frozen' => 'Dibekukan',
        default => 'Aktif',
    };

    $kycColor = match ($user->kyc_status) {
        'verified' => 'text-gray-900',
        'rejected' => 'text-red-600',
        default => 'text-gray-500',
    };

    $kycLabel = match ($user->kyc_status) {
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
        'pending' => 'Menunggu',
        default => 'Belum Terverifikasi',
    };

    $assetText = $user->wallets->isNotEmpty()
        ? $user->wallets->map(fn ($wallet) => rtrim(rtrim($wallet->balance, '0'), '.') . ' ' . $wallet->asset)->implode(', ')
        : 'Tidak ada data dompet';
@endphp

<a href="{{ route('admin.users.show', $user) }}" class="block rounded-lg border border-gray-200 bg-white p-5">
    <div class="min-w-0">
        <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
        <p class="truncate text-sm text-gray-500">{{ $user->email }}</p>
        @if ($user->phone)
            <p class="truncate text-xs text-gray-400">{{ $user->phone }}</p>
        @endif
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-gray-100 pt-4">
        <div>
            <dt class="text-xs text-gray-500">Akun</dt>
            <dd class="mt-0.5 text-sm font-medium {{ $accountColor }}">{{ $accountLabel }}</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">KYC</dt>
            <dd class="mt-0.5 text-sm font-medium {{ $kycColor }}">{{ $kycLabel }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-xs text-gray-500">Total Aset</dt>
            <dd class="mt-0.5 truncate text-sm font-medium text-gray-900">{{ $assetText }}</dd>
        </div>
        <div class="col-span-2">
            <dt class="text-xs text-gray-500">Terdaftar</dt>
            <dd class="mt-0.5 text-sm text-gray-700">{{ $user->created_at->format('d M Y') }}</dd>
        </div>
    </dl>
</a>
