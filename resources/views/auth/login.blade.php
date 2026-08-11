<x-guest-layout heading="Welcome Back" subtitle="Sign in to your account">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="mb-1.5" />
            <x-text-input id="email" class="block w-full rounded-lg px-3.5 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="mb-1.5" />
            <x-password-input id="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex flex-wrap items-center justify-between gap-2">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600 rounded-sm" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <x-auth-primary-button>
            {{ __('Login') }}
        </x-auth-primary-button>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 text-center text-sm text-gray-600">
            {{ __("Don't have an account?") }}
            <a href="{{ route('register') }}" class="font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600 rounded-sm">
                {{ __('Register') }}
            </a>
        </p>
    @endif
</x-guest-layout>
