<x-guest-layout heading="Confirm Password" subtitle="For security reasons, please confirm your password before continuing.">
    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="mb-1.5" />
            <x-password-input id="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Confirm Password Button -->
        <x-auth-primary-button>
            {{ __('Confirm Password') }}
        </x-auth-primary-button>
    </form>
</x-guest-layout>
