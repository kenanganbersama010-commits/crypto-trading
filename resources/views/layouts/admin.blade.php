<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin - {{ \App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            /* Sidebar nav scrollbar: nearly invisible at rest, only shows faintly on hover */
            nav::-webkit-scrollbar {
                width: 4px;
            }

            nav::-webkit-scrollbar-track {
                background: transparent;
            }

            nav::-webkit-scrollbar-thumb {
                background: transparent;
                border-radius: 3px;
            }

            nav:hover::-webkit-scrollbar-thumb {
                background: rgba(237, 233, 254, 0.2);
            }

            nav {
                scrollbar-width: thin;
                scrollbar-color: transparent transparent;
            }

            nav:hover {
                scrollbar-color: rgba(237, 233, 254, 0.2) transparent;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div
            x-data="{
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('adminSidebarCollapsed') === 'true',
                toggleSidebarCollapse() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed);
                },
            }"
            class="min-h-screen bg-gray-50 lg:flex"
        >

            <!-- Mobile overlay -->
            <div
                x-show="sidebarOpen"
                x-transition:enter="transition-opacity ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden"
                style="display: none;"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-20' : 'lg:w-64']"
                class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full transform flex-col bg-gradient-to-b from-violet-900 to-violet-800 transition-all duration-200 ease-in-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shrink-0"
            >
                <div class="flex h-16 shrink-0 items-center justify-center overflow-hidden border-b border-violet-800 px-6">
                    <span class="whitespace-nowrap text-base font-semibold tracking-tight text-white" :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        {{ \App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading') }}
                    </span>
                    <div
                        class="hidden h-8 w-8 items-center justify-center rounded-full bg-violet-700 text-sm font-semibold text-white"
                        :class="sidebarCollapsed ? 'lg:flex' : ''"
                    >
                        {{ Str::of(\App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading'))->substr(0, 1)->upper() }}
                    </div>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                    <div>
                        <x-admin.nav-section title="Utama" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="{{ route('admin.dashboard') }}" icon="dashboard" :active="request()->routeIs('admin.dashboard')">
                                Dasbor
                            </x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Manajemen" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="{{ route('admin.users.index') }}" icon="users" :active="request()->routeIs('admin.users.*')">Pengguna</x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Keuangan" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="{{ route('admin.deposits.index') }}" icon="deposits" :active="request()->routeIs('admin.deposits.*')">Deposit</x-admin.nav-item>
                            <x-admin.nav-item href="#" icon="withdrawals">Penarikan</x-admin.nav-item>
                            <x-admin.nav-item href="{{ route('admin.adjustment-history.index') }}" icon="transactions" :active="request()->routeIs('admin.adjustment-history.*')">Riwayat Penyesuaian Saldo</x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Kepatuhan" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="#" icon="compliance">Verifikasi KYC</x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Dukungan" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="#" icon="support">Layanan Pelanggan</x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Trading" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="#" icon="trading">Kontrol Trading</x-admin.nav-item>
                        </div>
                    </div>

                    <div>
                        <x-admin.nav-section title="Pengaturan" />
                        <div class="space-y-1">
                            <x-admin.nav-item href="{{ route('admin.settings.index') }}" icon="settings">Pengaturan</x-admin.nav-item>
                        </div>
                    </div>
                </nav>

                <div class="shrink-0 border-t border-violet-800 px-3 py-4">
                    <button
                        type="button"
                        x-on:click="$dispatch('open-modal', 'logout-confirm')"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-violet-700 px-4 py-2.5 text-sm font-medium text-white transition-colors duration-100 hover:bg-violet-600"
                    >
                        <x-icon name="logout" class="h-5 w-5 shrink-0" />
                        <span class="whitespace-nowrap" :class="sidebarCollapsed ? 'lg:hidden' : ''">Keluar</span>
                    </button>
                </div>
            </aside>

            <!-- Main column -->
            <div class="flex min-h-screen flex-col lg:min-w-0 lg:flex-1">
                <!-- Topbar -->
                <header class="sticky top-0 z-20 border-b border-gray-200 bg-white">
                    <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button
                                @click="sidebarOpen = true"
                                type="button"
                                class="-ml-1.5 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:hidden"
                            >
                                <span class="sr-only">Buka sidebar</span>
                                <x-icon name="menu" class="h-6 w-6" />
                            </button>

                            <button
                                @click="toggleSidebarCollapse()"
                                type="button"
                                class="hidden -ml-1.5 rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 lg:inline-flex"
                            >
                                <span class="sr-only" x-text="sidebarCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar'"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12H12m-8.25 5.25h16.5" />
                                </svg>
                            </button>

                            <h1 class="text-sm font-semibold text-gray-900">
                                @yield('title', 'Dasbor')
                            </h1>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-medium leading-tight text-gray-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs leading-tight text-gray-500">Admin</p>
                            </div>
                            @if(Auth::user()->profile_photo)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="h-9 w-9 rounded-full object-cover border-2 border-gray-200">
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-900 text-sm font-medium text-white">
                                    {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                    @yield('content')
                </main>
            </div>
        </div>

        <!-- Logout confirmation modal -->
        <x-modal name="logout-confirm" max-width="md" focusable>
            <div class="p-6">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100">
                    <x-icon name="logout" class="h-7 w-7 text-red-600" />
                </div>

                <h2 class="mt-4 text-center text-base font-semibold text-gray-900">Konfirmasi Keluar</h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Apakah Anda yakin ingin keluar dari sistem?
                </p>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                    <button
                        type="button"
                        x-on:click="$dispatch('close')"
                        class="flex-1 rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 sm:flex-none"
                    >
                        Batal
                    </button>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1 sm:flex-none">
                        @csrf
                        <button
                            type="submit"
                            class="w-full rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white"
                        >
                            Ya, Keluar
                        </button>
                    </form>
                </div>
            </div>
        </x-modal>
    </body>
</html>
