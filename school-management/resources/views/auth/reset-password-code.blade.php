<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">{{ __('Enter the six-digit code received by email, then choose your new password.') }}</div>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('password.code.reset') }}">@csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <div><x-input-label for="code" :value="__('Confirmation code')" /><x-text-input id="code" class="mt-1 block w-full" type="text" inputmode="numeric" name="code" maxlength="6" required autofocus /><x-input-error :messages="$errors->get('code')" class="mt-2" /></div>
        <div class="mt-4"><x-input-label for="password" :value="__('New password')" /><x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" /><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        <div class="mt-4"><x-input-label for="password_confirmation" :value="__('Confirm Password')" /><x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" /></div>
        <div class="mt-4 flex justify-end"><x-primary-button>{{ __('Reset password') }}</x-primary-button></div>
    </form>
</x-guest-layout>
