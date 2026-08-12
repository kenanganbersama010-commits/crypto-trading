{{-- Minimal foundation view for STEP 4.2 smoke testing only. Final UI is built in STEP 4.3. --}}
@extends('layouts.admin')

@section('title', 'Deposits')

@section('content')
    <div class="mx-auto max-w-7xl">
        <h2 class="text-xl font-semibold tracking-tight text-gray-900">Deposits</h2>

        @if ($deposits->isEmpty())
            <p class="mt-4 text-sm text-gray-500">No deposits found.</p>
        @else
            <ul class="mt-4 space-y-2 text-sm text-gray-700">
                @foreach ($deposits as $deposit)
                    <li>
                        <a href="{{ route('admin.deposits.show', $deposit) }}">
                            #{{ $deposit->id }} — {{ $deposit->user->name }} — {{ $deposit->asset }} {{ $deposit->amount }} — {{ $deposit->status }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
