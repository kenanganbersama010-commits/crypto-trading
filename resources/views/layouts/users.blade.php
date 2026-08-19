<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ \App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-50 lg:flex">

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
                class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                style="display: none;"
            ></div>

            <!-- Sidebar -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full transform flex-col bg-slate-900 transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 lg:shrink-0"
            >
                <div class="flex h-16 shrink-0 items-center px-6">
                    <span class="text-base font-semibold tracking-tight text-white">
                        {{ \App\Models\SystemSetting::get('dashboard_name', 'Crypto Trading') }}
                    </span>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
                    <div>
                        <x-nav-section title="Main" />
                        <div class="space-y-1">
                            <x-nav-item href="{{ route('dashboard') }}" icon="dashboard" :active="request()->routeIs('dashboard')">
                                Dashboard
                            </x-nav-item>
                        </div>
                    </div>

                    <div>
                        <x-nav-section title="Trading" />
                        <div class="space-y-1">
                            <x-nav-item href="#" icon="markets">Markets</x-nav-item>
                            <x-nav-item href="#" icon="trade">Trade</x-nav-item>
                        </div>
                    </div>

                    <div>
                        <x-nav-section title="Portfolio" />
                        <div class="space-y-1">
                            <x-nav-item href="#" icon="wallet">Wallet</x-nav-item>
                            <x-nav-item href="#" icon="transactions">Transactions</x-nav-item>
                        </div>
                    </div>

                    <div>
                        <x-nav-section title="Account" />
                        <div class="space-y-1">
                            <x-nav-item href="{{ route('profile.edit') }}" icon="profile" :active="request()->routeIs('profile.edit')">
                                Profile
                            </x-nav-item>
                            <x-nav-item href="#" icon="settings">Settings</x-nav-item>
                        </div>
                    </div>
                </nav>

                <div class="shrink-0 border-t border-slate-800 px-3 py-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="group flex w-full items-center gap-3 rounded-md border-l-2 border-transparent py-2 pl-2.5 pr-3 text-sm font-medium text-slate-300 transition-colors duration-100 hover:bg-slate-800/60 hover:text-white"
                        >
                            <x-icon name="logout" class="h-5 w-5 shrink-0 text-slate-400 group-hover:text-white" />
                            Logout
                        </button>
                    </form>
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
                                <span class="sr-only">Open sidebar</span>
                                <x-icon name="menu" class="h-6 w-6" />
                            </button>

                            <h1 class="text-sm font-semibold text-gray-900">
                                @yield('title', 'Dashboard')
                            </h1>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-medium leading-tight text-gray-900">{{ Auth::user()->name }}</p>
                                <p class="text-xs leading-tight text-gray-500">User</p>
                            </div>
                            @if(Auth::user()->profile_photo)
                                <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}" alt="Profile" class="h-9 w-9 rounded-full object-cover border-2 border-gray-200">
                            @else
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-100 text-sm font-medium text-violet-700">
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
    </body>
</html>
