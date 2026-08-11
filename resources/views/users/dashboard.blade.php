@extends('layouts.users')

@section('title', 'Dashboard')

@section('content')
    @php
        $holdings = [
            ['asset' => 'Bitcoin', 'symbol' => 'BTC', 'amount' => '0.42 BTC', 'value' => '$9,870.00', 'allocation' => 62],
            ['asset' => 'Ethereum', 'symbol' => 'ETH', 'amount' => '2.10 ETH', 'value' => '$4,830.00', 'allocation' => 30],
            ['asset' => 'Solana', 'symbol' => 'SOL', 'amount' => '18.5 SOL', 'value' => '$1,120.00', 'allocation' => 8],
        ];

        $transactions = [
            ['asset' => 'BTC', 'type' => 'Buy', 'amount' => '0.05 BTC', 'status' => 'Completed', 'date' => 'Aug 9, 2026'],
            ['asset' => 'ETH', 'type' => 'Sell', 'amount' => '0.80 ETH', 'status' => 'Completed', 'date' => 'Aug 8, 2026'],
            ['asset' => 'USDT', 'type' => 'Deposit', 'amount' => '$1,000.00', 'status' => 'Pending', 'date' => 'Aug 7, 2026'],
            ['asset' => 'SOL', 'type' => 'Buy', 'amount' => '5.0 SOL', 'status' => 'Failed', 'date' => 'Aug 6, 2026'],
        ];
    @endphp

    <div class="mx-auto max-w-7xl">
        <!-- Page header -->
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500">Overview of your account and trading activity.</p>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Available Balance" value="$12,450.00" />
            <x-stat-card label="Portfolio Value" value="$15,820.00" />
            <x-stat-card label="Today's P&amp;L" value="+$245.20" valueClass="text-green-600" />
            <x-stat-card label="Open Orders" value="4" />
        </div>

        <!-- Portfolio overview + transactions -->
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white lg:col-span-1">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Portfolio Overview</h3>
                </div>

                <div class="space-y-4 px-5 py-4">
                    @foreach ($holdings as $holding)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-900">{{ $holding['asset'] }} <span class="text-gray-400">({{ $holding['symbol'] }})</span></span>
                                <span class="text-gray-500">{{ $holding['value'] }}</span>
                            </div>
                            <div class="mt-1.5 h-1.5 w-full rounded-full bg-gray-100">
                                <div class="h-1.5 rounded-full bg-violet-500" style="width: {{ $holding['allocation'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white lg:col-span-2">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h3 class="text-sm font-semibold text-gray-900">Recent Transactions</h3>
                </div>

                <!-- Table (md and up) -->
                <div class="hidden md:block">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Asset</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Type</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Amount</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $trx)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="px-5 py-3 text-sm text-gray-900">{{ $trx['asset'] }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600">{{ $trx['type'] }}</td>
                                    <td class="px-5 py-3 text-right text-sm text-gray-900">{{ $trx['amount'] }}</td>
                                    <td class="px-5 py-3"><x-badge :status="$trx['status']" /></td>
                                    <td class="px-5 py-3 text-sm text-gray-500">{{ $trx['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Card list (below md) -->
                <div class="divide-y divide-gray-100 md:hidden">
                    @foreach ($transactions as $trx)
                        <div class="flex items-center justify-between px-5 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $trx['asset'] }} &middot; {{ $trx['type'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $trx['date'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-900">{{ $trx['amount'] }}</p>
                                <div class="mt-1"><x-badge :status="$trx['status']" /></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
