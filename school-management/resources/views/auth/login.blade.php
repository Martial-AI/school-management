<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form id="login-form" method="POST" action="{{ route('login') }}">
        @csrf
        <input id="device-model" type="hidden" name="device_model">
        <input id="device-platform" type="hidden" name="device_platform">
        <input id="device-browser" type="hidden" name="device_browser">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            @if ($errors->first('email') !== __('Your account has been suspended. Contact the Manager.'))
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            @endif
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        const loginForm = document.getElementById('login-form');
        let deviceDetailsReady = false;
        loginForm.addEventListener('submit', async event => {
            if (deviceDetailsReady) return;
            event.preventDefault();
            let platform = navigator.userAgentData?.platform || navigator.platform || '';
            let model = '';
            let browser = '';
            try {
                if (navigator.userAgentData?.getHighEntropyValues) {
                    const details = await navigator.userAgentData.getHighEntropyValues(['model', 'platform']);
                    model = details.model || '';
                    platform = details.platform || platform;
                    const brands = (navigator.userAgentData.brands || []).map(item => item.brand);
                    browser = brands.find(brand => /Microsoft Edge|Google Chrome|Opera|Samsung Internet|Firefox/i.test(brand)) || brands.find(brand => !/not.*brand|chromium/i.test(brand)) || '';
                }
            } catch (error) {}
            document.getElementById('device-model').value = model;
            document.getElementById('device-platform').value = platform;
            document.getElementById('device-browser').value = browser;
            deviceDetailsReady = true;
            loginForm.requestSubmit();
        });
    </script>

    @if(session('pending_login_approval'))
        <div id="login-pending-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-xl text-amber-700">⌛</div><h2 class="mt-4 text-lg font-semibold text-gray-900">{{ __('Connection request pending') }}</h2><p id="login-pending-message" class="mt-2 text-sm text-gray-600">{{ __('This account is currently connected on another device. Waiting for approval from that device.') }}</p><div class="mt-5 flex justify-center gap-1"><span class="h-2 w-2 animate-bounce rounded-full bg-emerald-600"></span><span class="h-2 w-2 animate-bounce rounded-full bg-emerald-600 [animation-delay:150ms]"></span><span class="h-2 w-2 animate-bounce rounded-full bg-emerald-600 [animation-delay:300ms]"></span></div></div></div>
        <script>const pendingCheck=setInterval(async()=>{const response=await fetch('{{ route('login-approval.status') }}',{headers:{Accept:'application/json'}});const data=await response.json();if(data.status==='approved'){clearInterval(pendingCheck);window.location=data.redirect;return}if(data.status==='declined'||data.status==='expired'){clearInterval(pendingCheck);document.getElementById('login-pending-message').textContent=data.status==='declined'?'{{ __('Connection refused by the connected device.') }}':'{{ __('The connection request has expired. Please try again.') }}';setTimeout(()=>document.getElementById('login-pending-modal').remove(),2500)}},3000);</script>
    @endif

    @if(session('pending_login_approval'))<script>const waitingModal=document.getElementById('login-pending-modal');const cancelButton=document.createElement('button');cancelButton.type='button';cancelButton.className='mt-5 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700';cancelButton.textContent='{{ __('Cancel') }}';cancelButton.addEventListener('click',async()=>{await fetch('{{ route('login-approval.cancel', session('pending_login_approval')) }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,Accept:'application/json'}});clearInterval(pendingCheck);waitingModal.remove()});waitingModal.querySelector('.mt-5.flex').after(cancelButton);</script>@endif

    @if ($errors->first('email') === __('Your account has been suspended. Contact the Manager.'))
        <div id="suspended-account-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl">
                <h2 class="text-lg font-semibold text-amber-800">{{ __('Account suspended') }}</h2>
                <p class="mt-2 text-gray-700">{{ __('Your account has been suspended. Contact the Manager.') }}</p>
                <button type="button" onclick="document.getElementById('suspended-account-modal').remove()" class="mt-5 rounded-lg bg-emerald-700 px-5 py-2 text-white">{{ __('OK') }}</button>
            </div>
        </div>
    @endif
</x-guest-layout>
