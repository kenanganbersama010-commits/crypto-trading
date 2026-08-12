{{-- Minimal foundation view for STEP 4.2 smoke testing only. Final UI is built in STEP 4.3. --}}
@extends('layouts.admin')

@section('title', 'Deposit Detail')

@section('content')
    <div class="mx-auto max-w-7xl">
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Deposit Detail</h2>

        <dl class="mt-4 space-y-2 text-sm text-gray-700">
            <div><dt class="inline font-medium">User:</dt> <dd class="inline">{{ $deposit->user->name }} ({{ $deposit->user->email }})</dd></div>
            <div><dt class="inline font-medium">Method:</dt> <dd class="inline">{{ $deposit->method }}</dd></div>
            <div><dt class="inline font-medium">Asset:</dt> <dd class="inline">{{ $deposit->asset }}</dd></div>
            <div><dt class="inline font-medium">Amount:</dt> <dd class="inline">{{ $deposit->amount }}</dd></div>
            <div><dt class="inline font-medium">Status:</dt> <dd class="inline">{{ $deposit->status }}</dd></div>
        </dl>

        <a href="{{ route('admin.deposits.index') }}" class="mt-4 inline-block text-sm text-violet-600">Back to Deposits</a>
    </div>
@endsection
