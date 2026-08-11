<x-guest-layout heading="Forgot Password?" subtitle="Enter your email address and we'll send you a password reset link.">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="mb-1.5" />
            <x-text-input id="email" class="block w-full rounded-lg px-3.5 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Send Reset Link Button -->
        <x-auth-primary-button>
            {{ __('Send Reset Link') }}
        </x-auth-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <a href="{{ route('login') }}" class="font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600 rounded-sm">
            {{ __('Back to Login') }}
        </a>
    </p>
</x-guest-layout>
