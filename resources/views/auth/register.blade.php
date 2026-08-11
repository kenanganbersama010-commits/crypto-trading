<x-guest-layout heading="Create Account" subtitle="Create your account to get started" max-width="480px">
    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" class="mb-1.5" />
            <x-text-input id="name" class="block w-full rounded-lg px-3.5 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="mb-1.5" />
            <x-text-input id="email" class="block w-full rounded-lg px-3.5 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="mb-1.5" />
            <x-password-input id="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="mb-1.5" />
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
        </div>

        <!-- Create Account Button -->
        <x-auth-primary-button>
            {{ __('Create Account') }}
        </x-auth-primary-button>
    </form>

    <p class="mt-8 text-center text-sm text-gray-600">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600 rounded-sm">
            {{ __('Login') }}
        </a>
    </p>
</x-guest-layout>
