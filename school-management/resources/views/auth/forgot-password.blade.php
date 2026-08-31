<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">{{ __('Enter the email address of your account. A six-digit confirmation code will be sent to this address.') }}</div>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <div><x-input-label for="email" :value="__('Email')" /><x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus /><x-input-error :messages="$errors->get('email')" class="mt-2" /></div>
        <div class="mt-4 flex justify-end"><x-primary-button>{{ __('Send confirmation code') }}</x-primary-button></div>
    </form>
</x-guest-layout>
