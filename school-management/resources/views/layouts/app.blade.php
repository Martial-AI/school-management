<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/gut-logo.png') }}">
        <style>
            #activity-badge { top: -0.35rem; right: -0.35rem; z-index: 10; pointer-events: none; }
            button:has(#activity-badge) svg { position: relative; z-index: 0; }
            #activity-history-modal { z-index: 100; align-items: flex-start; overflow-y: auto; padding: 5.5rem 1rem 1rem; }
            #activity-history-modal > div { max-height: calc(100vh - 6.5rem); }
            @media (min-width: 640px) { #activity-history-modal { align-items: center; padding: 1.5rem; } #activity-history-modal > div { max-height: 85vh; } }
        </style>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-emerald-50 via-slate-50 to-slate-100">
            @include('layouts.navigation')
            @php($serverLoginApproval = auth()->check() ? \App\Models\LoginApproval::where('user_id', auth()->id())->where('status', 'pending')->where('expires_at', '>', now())->latest()->first() : null)
            @if($serverLoginApproval)
                <div id="server-device-login-approval" style="position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;background:rgb(0 0 0 / .4);padding:1rem">
                    <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-xl text-amber-700">!</div><h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('New connection request') }}</h2><p class="mt-2 text-sm text-slate-600">{{ __('Another device wants to connect to this account. Accepting will sign you out of this device.') }}</p><div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-left"><p class="font-semibold text-slate-800">{{ $serverLoginApproval->requester_device_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $serverLoginApproval->requester_device_platform }} : {{ $serverLoginApproval->requester_ip_address ?: '—' }}</p></div><div class="mt-6 flex justify-center gap-3"><button type="button" data-server-decision="declined" class="rounded-lg border px-4 py-2">{{ __('Refuse') }}</button><button type="button" data-server-decision="approved" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">{{ __('Accept') }}</button></div></div>
                </div>
                <script>document.querySelectorAll('[data-server-decision]').forEach(button=>button.addEventListener('click',async()=>{const response=await fetch('{{ route('login-approval.decide', $serverLoginApproval) }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({decision:button.dataset.serverDecision})});const result=await response.json();if(result.status==='approved')window.location=result.redirect;else document.getElementById('server-device-login-approval').remove()}));</script>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-slate-100 bg-white/80 shadow-sm backdrop-blur">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            @if (session('success'))
                <div id="success-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl">
                        <h3 class="text-lg font-semibold text-emerald-800">{{ __('Action successful') }}</h3>
                        <p class="mt-2 text-gray-700">{{ session('success') }}</p>
                        <button type="button" onclick="document.getElementById('success-modal').remove()" class="mt-5 rounded-lg bg-emerald-700 px-5 py-2 text-white">{{ __('OK') }}</button>
                    </div>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div id="error-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl">
                        <h3 class="text-lg font-semibold text-red-800">{{ __('Action not completed') }}</h3>
                        <p class="mt-2 text-gray-700">{{ session('error') ?: $errors->first() }}</p>
                        <button type="button" onclick="document.getElementById('error-modal').remove()" class="mt-5 rounded-lg bg-gray-700 px-5 py-2 text-white">{{ __('OK') }}</button>
                    </div>
                </div>
            @endif
        </div>
        <script>
            document.querySelectorAll('input[type="password"]').forEach(function (input) {
                const parent = input.parentElement;
                parent.classList.add('relative');
                input.classList.add('pr-20');
                const button = document.createElement('button');
                button.type = 'button';
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>';
                button.title = @json(__('Show password'));
                button.setAttribute('aria-label', @json(__('Show password')));
                button.className = 'absolute right-2 top-1/2 -translate-y-1/2 text-emerald-700';
                button.addEventListener('click', function () {
                    const visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.title = visible ? @json(__('Show password')) : @json(__('Hide password'));
                    button.setAttribute('aria-label', button.title);
                });
                parent.appendChild(button);
            });
        </script>
        <script>
            let applicationDataVersion = null;
            window.applicationNavigating = false;
            let formHasUnsavedChanges = false;
            document.addEventListener('click', event => { if (event.target.closest('a')) window.applicationNavigating = true; });
            document.addEventListener('submit', () => { window.applicationNavigating = true; });
            document.querySelectorAll('form').forEach(form => form.addEventListener('input', () => { formHasUnsavedChanges = true; }, {once: true}));
            document.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => { formHasUnsavedChanges = false; }));
            function hasOpenModal() { return [...document.querySelectorAll('[id$="modal"]')].some(element => !element.classList.contains('hidden')); }
            async function synchronizeApplicationData() {
                try {
                    const response = await fetch('{{ route('data-sync.version') }}', {headers: {Accept: 'application/json'}, cache: 'no-store'});
                    if (!response.ok) return;
                    const data = await response.json();
                    if (!applicationDataVersion) { applicationDataVersion = data.version; return; }
                    if (applicationDataVersion !== data.version) {
                        applicationDataVersion = data.version;
                        if (!formHasUnsavedChanges && !hasOpenModal()) { window.applicationNavigating = true; window.location.reload(); }
                    }
                } catch (error) {}
            }
            synchronizeApplicationData();
            setInterval(synchronizeApplicationData, 5000);
        </script>
        <script>
            function sessionHeartbeat() {
                fetch('{{ route('session-heartbeat.ping') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json'}}).catch(() => {});
            }
            sessionHeartbeat();
            setInterval(sessionHeartbeat, 5000);
        </script>
        <script>
            let activeApprovalId = null;
            async function checkLoginApproval() {
                try {
                    const response = await fetch('{{ route('login-approval.active') }}', {headers: {Accept: 'application/json'}});
                    if (!response.ok) return;
                    const data = await response.json();
                    if (!data.pending) { document.getElementById('device-login-approval')?.remove(); activeApprovalId = null; return; }
                    if (activeApprovalId === data.approval_id) return;
                    activeApprovalId = data.approval_id;
                    const modal = document.createElement('div');
                    modal.id = 'device-login-approval';
                    modal.className = 'fixed inset-0 z-[80] flex items-center justify-center bg-black/40 p-4';
                    modal.style.cssText = 'position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;';
                    modal.innerHTML = `<div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-xl"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-xl text-amber-700">!</div><h2 class="mt-4 text-lg font-semibold text-gray-900">{{ __('New connection request') }}</h2><p class="mt-2 text-sm text-gray-600">{{ __('Another device wants to connect to this account. Accepting will sign you out of this device.') }}</p><div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-left"><p data-requester-device class="font-semibold text-slate-800"></p><p data-requester-ip class="mt-0.5 text-xs text-slate-500"></p></div><div class="mt-6 flex justify-center gap-3"><button data-decision="declined" class="rounded-lg border px-4 py-2">{{ __('Refuse') }}</button><button data-decision="approved" class="rounded-lg bg-emerald-700 px-4 py-2 text-white">{{ __('Accept') }}</button></div></div>`;
                    document.body.appendChild(modal);
                    modal.querySelector('[data-requester-device]').textContent = data.requester_device || '{{ __('Unknown device') }}';
                    modal.querySelector('[data-requester-ip]').textContent = `${data.requester_platform || '{{ __('Device') }}'} : ${data.requester_ip || '—'}`;
                    modal.querySelectorAll('[data-decision]').forEach(button => button.addEventListener('click', async () => {
                        const response = await fetch(`/login-approval/${data.approval_id}/decision`, {method: 'POST', headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json', 'Content-Type': 'application/json'}, body: JSON.stringify({decision: button.dataset.decision})});
                        const result = await response.json();
                        if (result.status === 'approved') window.location = result.redirect; else { modal.remove(); activeApprovalId = null; }
                    }));
                } catch (error) {}
            }
            checkLoginApproval();
            setInterval(checkLoginApproval, 3000);
        </script>
        <script>
            window.confirmDeletion = message => new Promise(resolve => {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 z-[120] flex items-center justify-center bg-black/40 p-4';
                modal.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;';
                modal.innerHTML = `<div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl"><div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-xl text-red-700">!</div><h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Confirm deletion') }}</h2><p class="mt-2 text-sm text-slate-600"></p><div class="mt-6 flex justify-center gap-3"><button type="button" data-cancel class="rounded-lg border border-slate-300 px-4 py-2 text-sm">{{ __('Cancel') }}</button><button type="button" data-confirm class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white">{{ __('Confirm') }}</button></div></div>`;
                modal.querySelector('p').textContent = message;
                modal.querySelector('[data-cancel]').onclick = () => { modal.remove(); resolve(false); };
                modal.querySelector('[data-confirm]').onclick = () => { modal.remove(); resolve(true); };
                document.body.appendChild(modal);
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('p').forEach((amount) => {
                    if (!/\bAr\s*$/.test(amount.textContent.trim()) || amount.dataset.moneyProtected) return;
                    amount.dataset.moneyProtected = 'true';
                    amount.dataset.moneyValue = amount.textContent.trim();
                    amount.classList.add('cursor-pointer', 'select-none');
                    amount.textContent = '***** Ar';
                    amount.dataset.hidden = 'true';
                    amount.title = '{{ __('Show amount') }}';
                    amount.addEventListener('click', () => {
                        const hidden = amount.dataset.hidden === 'true';
                        amount.textContent = hidden ? amount.dataset.moneyValue : '***** Ar';
                        amount.dataset.hidden = hidden ? 'false' : 'true';
                        amount.title = hidden ? '{{ __('Hide amount') }}' : '{{ __('Show amount') }}';
                    });
                });
            });
        </script>
    </body>
</html>
