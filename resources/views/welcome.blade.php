<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Crypto Trading') }} — Trade Smarter, Grow Faster</title>
        <meta name="description" content="A premium crypto trading platform. Track live markets, manage your portfolio, and trade with confidence.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-950 font-sans text-white antialiased" x-data="{ mobileMenuOpen: false }">

        {{-- ========================= NAVBAR ========================= --}}
        <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-md">
            <nav class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                {{-- Logo --}}
                <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-blue-500 text-sm font-bold text-white">
                        {{ Str::of(config('app.name', 'Crypto Trading'))->substr(0, 1)->upper() }}
                    </span>
                    <span class="text-base font-semibold tracking-tight text-white">
                        {{ config('app.name', 'Crypto Trading') }}
                    </span>
                </a>

                {{-- Desktop links --}}
                <div class="hidden items-center gap-8 lg:flex">
                    <a href="#markets" class="text-sm font-medium text-slate-300 transition-colors hover:text-white">Markets</a>
                    <a href="#trading" class="text-sm font-medium text-slate-300 transition-colors hover:text-white">Trading</a>
                    <a href="#assets" class="text-sm font-medium text-slate-300 transition-colors hover:text-white">Assets</a>
                    <a href="#features" class="text-sm font-medium text-slate-300 transition-colors hover:text-white">Features</a>
                    <a href="#about" class="text-sm font-medium text-slate-300 transition-colors hover:text-white">About</a>
                </div>

                {{-- Search + Auth (desktop) --}}
                <div class="hidden items-center gap-3 lg:flex">
                    <label class="relative">
                        <span class="sr-only">Search markets</span>
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path stroke-linecap="round" d="M21 21l-3.5-3.5" />
                        </svg>
                        <input
                            type="text"
                            placeholder="Search markets…"
                            class="w-48 rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 focus:border-violet-400/50 focus:outline-none focus:ring-0"
                        >
                    </label>

                    <a href="{{ route('login') }}" class="rounded-xl px-4 py-2 text-sm font-medium text-slate-200 transition-colors hover:text-white">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-opacity hover:opacity-90">
                        Register
                    </a>
                </div>

                {{-- Hamburger (mobile) --}}
                <button
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-300 hover:bg-white/5 hover:text-white lg:hidden"
                    aria-label="Toggle navigation menu"
                >
                    <x-icon x-show="!mobileMenuOpen" name="menu" class="h-6 w-6" />
                    <x-icon x-show="mobileMenuOpen" x-cloak name="close" class="h-6 w-6" />
                </button>
            </nav>

            {{-- Mobile menu --}}
            <div
                x-show="mobileMenuOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="border-t border-white/10 bg-slate-950 px-4 pb-6 pt-4 lg:hidden"
            >
                <div class="flex flex-col gap-1">
                    <a @click="mobileMenuOpen = false" href="#markets" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Markets</a>
                    <a @click="mobileMenuOpen = false" href="#trading" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Trading</a>
                    <a @click="mobileMenuOpen = false" href="#assets" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Assets</a>
                    <a @click="mobileMenuOpen = false" href="#features" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">Features</a>
                    <a @click="mobileMenuOpen = false" href="#about" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5 hover:text-white">About</a>
                </div>

                <div class="mt-4 flex flex-col gap-2 border-t border-white/10 pt-4">
                    <a href="{{ route('login') }}" class="w-full rounded-xl border border-white/15 px-4 py-2.5 text-center text-sm font-medium text-white">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="w-full rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        Register
                    </a>
                </div>
            </div>
        </header>

        <main>
            {{-- ========================= HERO ========================= --}}
            <section class="relative overflow-hidden bg-gradient-to-b from-violet-950/40 via-slate-950 to-slate-950" x-data="heroMarket()" x-init="init()">
                <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-2 lg:px-8 lg:py-32">
                    {{-- Copy --}}
                    <div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-medium text-violet-300">
                            <span class="h-1.5 w-1.5 rounded-full" :class="statusDot"></span>
                            <span x-text="statusText" :class="statusColor"></span>
                            Market
                        </span>

                        <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                            The Future of
                            <span class="bg-gradient-to-r from-violet-400 to-blue-400 bg-clip-text text-transparent">Crypto Trading</span>
                        </h1>

                        <p class="mt-5 max-w-lg text-base text-slate-400 sm:text-lg">
                            Trade smarter. Track the market. Grow your portfolio — all from a single, secure dashboard built for both new and experienced traders.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-opacity hover:opacity-90">
                                Start Trading
                            </a>
                            <a href="#markets" class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/10">
                                Explore Markets
                            </a>
                        </div>

                        <div class="mt-10 grid grid-cols-3 gap-4 border-t border-white/10 pt-6 sm:max-w-md">
                            <div>
                                <p class="text-xl font-bold text-white sm:text-2xl">120+</p>
                                <p class="mt-0.5 text-xs text-slate-500">Assets listed</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-white sm:text-2xl">24/7</p>
                                <p class="mt-0.5 text-xs text-slate-500">Market access</p>
                            </div>
                            <div>
                                <p class="text-xl font-bold text-white sm:text-2xl">150K+</p>
                                <p class="mt-0.5 text-xs text-slate-500">Active users</p>
                            </div>
                        </div>
                    </div>

                    {{-- Visual: Live market snapshot card --}}
                    <div class="relative">
                        <div class="absolute -inset-8 -z-10 rounded-full bg-gradient-to-br from-violet-600/20 to-blue-600/10 blur-3xl"></div>

                        {{-- Loading State --}}
                        <div x-show="isLoading" class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-lg backdrop-blur-sm">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-8 w-8 animate-pulse rounded-full bg-white/5"></span>
                                    <div class="space-y-2">
                                        <div class="h-4 w-24 animate-pulse rounded bg-white/5"></div>
                                        <div class="h-3 w-16 animate-pulse rounded bg-white/5"></div>
                                    </div>
                                </div>
                                <div class="h-6 w-16 animate-pulse rounded-lg bg-white/5"></div>
                            </div>
                            <div class="mt-4 h-10 w-40 animate-pulse rounded bg-white/5"></div>
                            <div class="mt-4 h-20 w-full animate-pulse rounded bg-white/5"></div>
                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-4">
                                <div class="h-16 animate-pulse rounded-xl bg-white/5"></div>
                                <div class="h-16 animate-pulse rounded-xl bg-white/5"></div>
                            </div>
                        </div>

                        {{-- Error State --}}
                        <div x-show="!isLoading && error" x-cloak class="rounded-2xl border border-red-500/20 bg-red-500/5 p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-white">Market data unavailable</p>
                            <p class="mt-1 text-xs text-slate-400">Reconnecting...</p>
                        </div>

                        {{-- Live Market Card --}}
                        <div x-show="!isLoading && !error && market" x-cloak class="rounded-2xl border border-white/10 bg-white/[0.03] p-5 shadow-lg backdrop-blur-sm">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-500/15 text-xs font-bold text-amber-400">₿</span>
                                    <div>
                                        <p class="text-sm font-semibold text-white">BTC / USDT</p>
                                        <p class="text-xs text-slate-500">Bitcoin</p>
                                    </div>
                                </div>
                                <span class="rounded-lg px-2 py-1 text-xs font-semibold" :class="getChangeColor(market.change24h)" x-text="formatChange(market.change24h)"></span>
                            </div>

                            <p class="mt-4 text-3xl font-bold tracking-tight text-white">
                                $<span x-text="formatPrice(market.price)"></span>
                            </p>

                            {{-- Mini area chart --}}
                            <svg viewBox="0 0 300 80" class="mt-4 h-20 w-full" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="heroChartFill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" :stop-color="isPositive(market.change24h) ? '#34d399' : '#f87171'" stop-opacity="0.35" />
                                        <stop offset="100%" :stop-color="isPositive(market.change24h) ? '#34d399' : '#f87171'" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                <path 
                                    :d="isPositive(market.change24h) ? 'M0,55 L25,50 L50,58 L75,40 L100,45 L125,30 L150,35 L175,20 L200,28 L225,15 L250,22 L275,10 L300,18 L300,80 L0,80 Z' : 'M0,25 L25,30 L50,22 L75,40 L100,35 L125,50 L150,45 L175,60 L200,52 L225,65 L250,58 L275,70 L300,62 L300,80 L0,80 Z'" 
                                    fill="url(#heroChartFill)" 
                                />
                                <polyline 
                                    :points="isPositive(market.change24h) ? '0,55 25,50 50,58 75,40 100,45 125,30 150,35 175,20 200,28 225,15 250,22 275,10 300,18' : '0,25 25,30 50,22 75,40 100,35 125,50 150,45 175,60 200,52 225,65 250,58 275,70 300,62'" 
                                    fill="none" 
                                    :stroke="isPositive(market.change24h) ? '#34d399' : '#f87171'" 
                                    stroke-width="2" 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                />
                            </svg>

                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-4">
                                <div class="rounded-xl bg-white/5 px-3 py-2">
                                    <p class="text-[11px] text-slate-500">24h High</p>
                                    <p class="mt-0.5 text-sm font-semibold text-white">$<span x-text="formatPrice(market.high24h)"></span></p>
                                </div>
                                <div class="rounded-xl bg-white/5 px-3 py-2">
                                    <p class="text-[11px] text-slate-500">24h Low</p>
                                    <p class="mt-0.5 text-sm font-semibold text-white">$<span x-text="formatPrice(market.low24h)"></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ========================= MARKET OVERVIEW ========================= --}}
            <section id="markets" class="scroll-mt-16 border-t border-white/10 bg-slate-950 py-16 sm:py-20" x-data="liveMarkets()" x-init="init()">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">Live Crypto Market</h2>
                            <p class="mt-1.5 text-sm text-slate-400">A snapshot of the assets available to trade.</p>
                        </div>
                        
                        {{-- Live Status Indicator --}}
                        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1">
                            <span class="h-2 w-2 rounded-full" :class="statusDot"></span>
                            <span class="text-xs" :class="statusColor" x-text="statusText"></span>
                        </div>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="isLoading" class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="i in 6" :key="i">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 animate-pulse rounded-full bg-white/5"></div>
                                        <div class="space-y-2">
                                            <div class="h-4 w-20 animate-pulse rounded bg-white/5"></div>
                                            <div class="h-3 w-12 animate-pulse rounded bg-white/5"></div>
                                        </div>
                                    </div>
                                    <div class="h-6 w-16 animate-pulse rounded-lg bg-white/5"></div>
                                </div>
                                <div class="mt-4 h-6 w-32 animate-pulse rounded bg-white/5"></div>
                                <div class="mt-2 h-8 w-full animate-pulse rounded bg-white/5"></div>
                            </div>
                        </template>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!isLoading && error" x-cloak class="mt-8 rounded-2xl border border-red-500/20 bg-red-500/5 p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-white">Failed to load market data</p>
                        <p class="mt-1 text-xs text-slate-400" x-text="error"></p>
                    </div>

                    {{-- Market Cards --}}
                    <div x-show="!isLoading && !error" x-cloak class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <template x-for="market in markets" :key="market.symbol">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 transition-colors hover:bg-white/[0.05]">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-violet-500/20 to-blue-500/20 text-xs font-bold text-white" x-text="market.shortSymbol"></span>
                                        <div>
                                            <p class="text-sm font-semibold text-white" x-text="market.name"></p>
                                            <p class="text-xs text-slate-500" x-text="market.shortSymbol"></p>
                                        </div>
                                    </div>
                                    <span class="rounded-lg px-2 py-1 text-xs font-semibold" :class="getChangeColor(market.change24h)" x-text="formatChange(market.change24h)"></span>
                                </div>
                                <p class="mt-4 text-xl font-bold text-white">
                                    $<span x-text="formatPrice(market.price)"></span>
                                </p>

                                {{-- Mini Chart Sparkline --}}
                                <svg viewBox="0 0 120 32" class="mt-2 h-8 w-full" preserveAspectRatio="none">
                                    <polyline 
                                        points="0,24 15,22 30,25 45,16 60,18 75,10 90,14 105,6 120,9" 
                                        fill="none" 
                                        stroke="#34d399" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round"
                                        :style="isPositive(market.change24h) ? 'display: block;' : 'display: none;'" 
                                    />
                                    <polyline 
                                        points="0,8 15,10 30,7 45,15 60,13 75,20 90,17 105,24 120,22" 
                                        fill="none" 
                                        stroke="#f87171" 
                                        stroke-width="2" 
                                        stroke-linecap="round" 
                                        stroke-linejoin="round"
                                        :style="!isPositive(market.change24h) ? 'display: block;' : 'display: none;'" 
                                    />
                                </svg>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            {{-- ========================= MARKET STATISTICS ========================= --}}
            <section class="border-t border-white/10 bg-slate-950 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">Market at a Glance</h2>

                    @php
                        $stats = [
                            ['label' => 'Market Cap', 'value' => '$2.31T'],
                            ['label' => '24h Volume', 'value' => '$84.6B'],
                            ['label' => 'BTC Dominance', 'value' => '51.2%'],
                            ['label' => 'Active Users', 'value' => '150K+'],
                            ['label' => 'Supported Assets', 'value' => '120+'],
                        ];
                    @endphp

                    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($stats as $s)
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4">
                                <p class="text-xs text-slate-500">{{ $s['label'] }}</p>
                                <p class="mt-1.5 text-lg font-bold text-white sm:text-xl">{{ $s['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ========================= TRADING EXPERIENCE / FEATURES ========================= --}}
            <section id="features" class="scroll-mt-16 border-t border-white/10 bg-slate-950 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Everything You Need to Trade Smarter</h2>
                        <p class="mt-2 text-sm text-slate-400 sm:text-base">A focused toolset for tracking markets and managing your portfolio.</p>
                    </div>

                    @php
                        $features = [
                            ['icon' => 'markets', 'title' => 'Real-time Market Data', 'desc' => 'Follow price movement across major assets in one dashboard.'],
                            ['icon' => 'compliance', 'title' => 'Secure Trading', 'desc' => 'Layered account protections designed around your trading activity.'],
                            ['icon' => 'trading', 'title' => 'Advanced Analytics', 'desc' => 'Charts and history that help you understand market trends.'],
                            ['icon' => 'wallet', 'title' => 'Portfolio Management', 'desc' => 'Track balances and asset allocation from a single view.'],
                        ];
                    @endphp

                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($features as $f)
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/20 to-blue-500/20 text-violet-300">
                                    <x-icon :name="$f['icon']" class="h-5 w-5" />
                                </span>
                                <p class="mt-4 text-sm font-semibold text-white">{{ $f['title'] }}</p>
                                <p class="mt-1.5 text-sm text-slate-400">{{ $f['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ========================= LIVE MARKET CHART ========================= --}}
            <section id="trading" class="scroll-mt-16 border-t border-white/10 bg-slate-950 py-16 sm:py-20" x-data="liveMarket()" x-init="init()">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-2xl">
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">Live Market Chart</h2>
                            <p class="mt-2 text-sm text-slate-400 sm:text-base">Real-time candlestick chart with live price updates.</p>
                        </div>
                        
                        {{-- Connection Status --}}
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" :class="statusDot"></span>
                            <span class="text-xs font-medium" :class="statusColor" x-text="statusText"></span>
                        </div>
                    </div>

                    {{-- Loading State --}}
                    <div x-show="isLoading" class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:p-6">
                        <div class="flex items-center justify-center py-20">
                            <div class="flex flex-col items-center gap-3">
                                <div class="h-8 w-8 animate-spin rounded-full border-2 border-white/10 border-t-violet-500"></div>
                                <p class="text-sm text-slate-400">Loading market data...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Error State --}}
                    <div x-show="!isLoading && error" x-cloak class="mt-8 rounded-2xl border border-red-500/20 bg-red-500/5 p-4 sm:p-6">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <svg class="h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="mt-3 text-sm font-semibold text-white">Market data temporarily unavailable</p>
                            <p class="mt-1 text-xs text-slate-400" x-text="error"></p>
                        </div>
                    </div>

                    {{-- Chart Container --}}
                    <div x-show="!isLoading && !error" x-cloak class="mt-8 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:p-6">
                        {{-- Market Header --}}
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/15 text-sm font-bold text-amber-400">₿</span>
                                <div>
                                    <p class="text-base font-semibold text-white" x-text="symbol"></p>
                                    <p class="text-xs text-slate-500">Bitcoin</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-white">$<span x-text="formatPrice(price)"></span></p>
                                    <p class="text-xs" :class="priceChangeColor">
                                        <span x-text="priceChangePercent >= 0 ? '+' : ''"></span><span x-text="priceChangePercent.toFixed(2)"></span>%
                                    </p>
                                </div>
                                <span class="rounded-lg px-2 py-1 text-xs font-semibold" :class="priceChangeBg + ' ' + priceChangeColor">
                                    <span x-text="priceChangePercent >= 0 ? '+' : ''"></span><span x-text="priceChangePercent.toFixed(2)"></span>%
                                </span>
                            </div>
                        </div>

                        {{-- Market Stats --}}
                        <div class="mt-4 grid grid-cols-3 gap-3 border-t border-white/10 pt-4 sm:grid-cols-4">
                            <div class="rounded-xl bg-white/5 px-3 py-2">
                                <p class="text-[11px] text-slate-500">24h High</p>
                                <p class="mt-0.5 text-sm font-semibold text-white">$<span x-text="formatPrice(high24h)"></span></p>
                            </div>
                            <div class="rounded-xl bg-white/5 px-3 py-2">
                                <p class="text-[11px] text-slate-500">24h Low</p>
                                <p class="mt-0.5 text-sm font-semibold text-white">$<span x-text="formatPrice(low24h)"></span></p>
                            </div>
                            <div class="rounded-xl bg-white/5 px-3 py-2">
                                <p class="text-[11px] text-slate-500">24h Volume</p>
                                <p class="mt-0.5 text-sm font-semibold text-white"><span x-text="formatVolume(volume24h)"></span></p>
                            </div>
                            <div class="hidden rounded-xl bg-white/5 px-3 py-2 sm:block">
                                <p class="text-[11px] text-slate-500">Timeframe</p>
                                <p class="mt-0.5 text-sm font-semibold text-white" x-text="interval"></p>
                            </div>
                        </div>

                        {{-- Chart --}}
                        <div class="mt-6">
                            <div id="live-chart" class="h-64 w-full sm:h-96"></div>
                        </div>

                        {{-- Disclaimer --}}
                        <div class="mt-4 border-t border-white/10 pt-4">
                            <p class="text-xs text-slate-500 text-center">
                                <template x-if="isConnected">
                                    <span>Live market data powered by Binance. <span class="text-emerald-400">● Connected</span></span>
                                </template>
                                <template x-if="!isConnected">
                                    <span>Market data service is currently unavailable. Using demo data.</span>
                                </template>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ========================= SECURITY ========================= --}}
            <section id="about" class="scroll-mt-16 border-t border-white/10 bg-slate-950 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Your Assets. Your Security.</h2>
                        <p class="mt-2 text-sm text-slate-400 sm:text-base">Protections built into every layer of your account, so you can trade with confidence.</p>
                    </div>

                    @php
                        $security = [
                            ['icon' => 'profile', 'title' => 'Secure Authentication', 'desc' => 'Account access protected by modern authentication practices.'],
                            ['icon' => 'wallet', 'title' => 'Asset Protection', 'desc' => 'Balances are tracked with precision and safeguarded controls.'],
                            ['icon' => 'transactions', 'title' => 'Transaction Monitoring', 'desc' => 'Deposits and withdrawals are reviewed before they settle.'],
                            ['icon' => 'compliance', 'title' => 'Account Security', 'desc' => 'Tools to help you keep your account under your control.'],
                        ];
                    @endphp

                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($security as $s)
                            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-5">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500/20 to-blue-500/20 text-violet-300">
                                    <x-icon :name="$s['icon']" class="h-5 w-5" />
                                </span>
                                <p class="mt-4 text-sm font-semibold text-white">{{ $s['title'] }}</p>
                                <p class="mt-1.5 text-sm text-slate-400">{{ $s['desc'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ========================= SUPPORTED ASSETS ========================= --}}
            <section id="assets" class="scroll-mt-16 border-t border-white/10 bg-slate-950 py-16 sm:py-20">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h2 class="text-2xl font-bold text-white sm:text-3xl">Trade Popular Assets</h2>
                    <p class="mt-2 text-sm text-slate-400 sm:text-base">A selection of the assets available on the platform.</p>

                    @php
                        $assets = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'XRP'];
                    @endphp

                    <div class="mt-8 grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                        @foreach ($assets as $a)
                            <div class="flex flex-col items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-center">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-violet-500/20 to-blue-500/20 text-xs font-bold text-white">
                                    {{ $a }}
                                </span>
                                <span class="text-xs font-medium text-slate-300">{{ $a }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ========================= CTA ========================= --}}
            <section class="border-t border-white/10 bg-slate-950 py-16 sm:py-20">
                <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
                    <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-violet-600/20 to-blue-600/10 p-10 sm:p-14">
                        <h2 class="text-2xl font-bold text-white sm:text-3xl">Ready to Start Trading?</h2>
                        <p class="mx-auto mt-3 max-w-md text-sm text-slate-400 sm:text-base">Create your account and explore the crypto market at your own pace.</p>

                        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                            <a href="{{ route('register') }}" class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-opacity hover:opacity-90 sm:w-auto">
                                Start Trading
                            </a>
                            <a href="#markets" class="inline-flex w-full items-center justify-center rounded-xl border border-white/15 bg-white/5 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/10 sm:w-auto">
                                Explore Markets
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        {{-- ========================= FOOTER ========================= --}}
        <footer class="border-t border-white/10 bg-slate-950">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:grid-cols-6">
                    <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                        <a href="{{ url('/') }}" class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-blue-500 text-xs font-bold text-white">
                                {{ Str::of(config('app.name', 'Crypto Trading'))->substr(0, 1)->upper() }}
                            </span>
                            <span class="text-sm font-semibold text-white">{{ config('app.name', 'Crypto Trading') }}</span>
                        </a>
                        <p class="mt-3 max-w-xs text-sm text-slate-500">A focused platform for tracking markets and managing your crypto portfolio.</p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Markets</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#markets" class="hover:text-white">Live Prices</a></li>
                            <li><a href="#assets" class="hover:text-white">Supported Assets</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trading</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#trading" class="hover:text-white">Terminal Preview</a></li>
                            <li><a href="#features" class="hover:text-white">Features</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#about" class="hover:text-white">About</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-white">Get Started</a></li>
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Support</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="{{ route('login') }}" class="hover:text-white">Login</a></li>
                            <li><a href="{{ route('register') }}" class="hover:text-white">Register</a></li>
                        </ul>
                    </div>
                </div>

                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 sm:flex-row">
                    <p class="text-xs text-slate-500">&copy; {{ now()->year }} {{ config('app.name', 'Crypto Trading') }}. All rights reserved.</p>
                    <p class="text-xs text-slate-600">Market data shown is for demonstration purposes only.</p>
                </div>
            </div>
        </footer>
    </body>
</html>
