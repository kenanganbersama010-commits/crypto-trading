@extends('layouts.admin')

@section('title', 'Pengguna')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Pengguna</h2>
            <p class="mt-1 text-sm text-gray-500">Kelola seluruh pengguna platform yang terdaftar.</p>
        </div>

        <div
            x-data="{ loading: false }"
            @submit="loading = true"
            @click="if ($event.target.closest('a')) loading = true"
        >
            <form method="GET" action="{{ route('admin.users.index') }}" class="space-y-4">
                <!-- Search Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <label for="search" class="sr-only">Cari pengguna</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari berdasarkan nama, email, atau nomor telepon..."
                        class="block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                    >
                </div>

                <!-- Filter Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                        <div>
                            <label for="account_status" class="block text-xs font-medium text-gray-500">Status Akun</label>
                            <select
                                id="account_status"
                                name="account_status"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                            >
                                <option value="">Semua</option>
                                <option value="active" @selected(request('account_status') === 'active')>Aktif</option>
                                <option value="frozen" @selected(request('account_status') === 'frozen')>Dibekukan</option>
                            </select>
                        </div>

                        <div>
                            <label for="kyc_status" class="block text-xs font-medium text-gray-500">Status KYC</label>
                            <select
                                id="kyc_status"
                                name="kyc_status"
                                class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                            >
                                <option value="">Semua</option>
                                <option value="unverified" @selected(request('kyc_status') === 'unverified')>Belum Terverifikasi</option>
                                <option value="pending" @selected(request('kyc_status') === 'pending')>Menunggu</option>
                                <option value="verified" @selected(request('kyc_status') === 'verified')>Terverifikasi</option>
                                <option value="rejected" @selected(request('kyc_status') === 'rejected')>Ditolak</option>
                            </select>
                        </div>

                        <div class="flex gap-2 lg:col-span-2">
                            <button type="submit" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white">
                                Terapkan Filter
                            </button>

                            @if (request()->hasAny(['search', 'account_status', 'kyc_status']))
                                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                                    Atur Ulang
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>

            <!-- User List -->
            <div class="mt-6">
                @if ($users->isEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-500">
                        @if (request()->hasAny(['search', 'account_status', 'kyc_status']))
                            Tidak ada pengguna yang sesuai dengan filter yang digunakan.
                        @else
                            Pengguna tidak ditemukan.
                        @endif
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($users as $user)
                            <x-admin.user-card :user="$user" />
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $users->links('admin.users.partials.pagination-id') }}
                    </div>
                @endif
            </div>

            <!--
                Global full-page loading overlay: shown while search/filter/pagination
                requests are in flight. Uses `fixed inset-0` (not `absolute`) so it is
                positioned against the viewport rather than a scoped content container —
                no ancestor in layouts/admin.blade.php sets transform/filter/perspective,
                so `fixed` here reaches the full viewport regardless of DOM nesting depth.
                z-[60] sits above every existing layer in the app (sidebar z-40, topbar
                z-20, mobile sidebar overlay z-30, dropdown/modal z-50), so it covers the
                sidebar and topbar too and blocks interaction with them while active.
                bg-white/60 stays translucent (not opaque) so backdrop-blur-sm has a
                visible page underneath it to actually blur. The spinner/text below are
                painted as this element's own content, not "behind" it, so backdrop-filter
                never touches them — they stay sharp automatically.
            -->
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
    </div>
@endsection
