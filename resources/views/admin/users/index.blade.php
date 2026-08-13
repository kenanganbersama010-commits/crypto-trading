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
                    <div class="relative">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" stroke-width="1.5" />
                            <path d="M21 21l-3.5-3.5" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari berdasarkan nama, email, atau nomor telepon..."
                            class="block w-full rounded-md border-gray-300 py-2.5 pl-10 {{ request('search') ? 'pr-9' : 'pr-3' }} text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                        >
                        @if (request('search'))
                            <a
                                href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => null]) }}"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                aria-label="Bersihkan pencarian"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                        <div>
                            <label for="account_status" class="block text-xs font-medium text-gray-500">Status Akun</label>
                            <div class="relative mt-1">
                                <select
                                    id="account_status"
                                    name="account_status"
                                    class="block w-full appearance-none rounded-md border-gray-300 py-2.5 pl-3 pr-9 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                                >
                                    <option value="">Semua</option>
                                    <option value="active" @selected(request('account_status') === 'active')>Aktif</option>
                                    <option value="frozen" @selected(request('account_status') === 'frozen')>Dibekukan</option>
                                </select>
                                <svg class="pointer-events-none absolute right-2.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                        </div>

                        <div>
                            <label for="kyc_status" class="block text-xs font-medium text-gray-500">Status KYC</label>
                            <div class="relative mt-1">
                                <select
                                    id="kyc_status"
                                    name="kyc_status"
                                    class="block w-full appearance-none rounded-md border-gray-300 py-2.5 pl-3 pr-9 text-sm text-gray-900 focus:border-violet-500 focus:ring-violet-500"
                                >
                                    <option value="">Semua</option>
                                    <option value="unverified" @selected(request('kyc_status') === 'unverified')>Belum Terverifikasi</option>
                                    <option value="pending" @selected(request('kyc_status') === 'pending')>Menunggu</option>
                                    <option value="verified" @selected(request('kyc_status') === 'verified')>Terverifikasi</option>
                                    <option value="rejected" @selected(request('kyc_status') === 'rejected')>Ditolak</option>
                                </select>
                                <svg class="pointer-events-none absolute right-2.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 9l6 6 6-6" />
                                </svg>
                            </div>
                        </div>

                        <div class="flex gap-2 lg:col-span-2">
                            <button type="submit" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white">
                                Terapkan Filter
                            </button>

                            @if (request()->hasAny(['search', 'account_status', 'kyc_status']))
                                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.5 12a7.5 7.5 0 0112.9-5.3M19.5 12a7.5 7.5 0 01-12.9 5.3M3 3v6h6" />
                                    </svg>
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
                    <div class="rounded-lg border border-gray-200 bg-white p-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        @if (request()->hasAny(['search', 'account_status', 'kyc_status']))
                            <p class="mt-3 text-sm text-gray-600">Tidak ada pengguna yang sesuai dengan filter yang digunakan.</p>
                            <p class="mt-1 text-xs text-gray-500">Coba ubah kata kunci atau filter.</p>
                        @else
                            <p class="mt-3 text-sm text-gray-600">Pengguna tidak ditemukan.</p>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Daftar Pengguna</h3>
                        <p class="mt-0.5 text-xs text-gray-500">Total {{ number_format($users->total()) }} pengguna terdaftar</p>
                    </div>

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
