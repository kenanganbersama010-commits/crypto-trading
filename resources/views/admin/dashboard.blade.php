@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6">
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Dashboard</h2>
            <p class="mt-1 text-sm text-gray-500">Overview of your platform.</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6">
            <p class="text-sm text-gray-600">
                Welcome, {{ Auth::user()->name }}. You are signed in as an administrator.
            </p>
        </div>
    </div>
@endsection
