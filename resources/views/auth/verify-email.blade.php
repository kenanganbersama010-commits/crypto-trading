<x-guest-layout heading="Verify Your Email" subtitle="Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed to you.">
    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-green-600">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <x-auth-primary-button>
            {{ __('Resend Verification Email') }}
        </x-auth-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="mt-6 block w-full text-center text-sm text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-600 rounded-sm">
            {{ __('Log Out') }}
        </button>
    </form>
</x-guest-layout>
