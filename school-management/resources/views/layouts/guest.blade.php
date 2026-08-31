<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/gut-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div id="guest-language-switcher" class="fixed top-4 right-4 z-[100000]" style="z-index:100000;left:auto;right:1rem">
            <button type="button" onclick="document.getElementById('guest-language-menu').classList.toggle('hidden')" class="inline-flex items-center rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-emerald-800 shadow-lg hover:bg-emerald-50">{{ strtoupper(app()->getLocale()) }}<svg class="ms-1 h-3.5 w-3.5 fill-current" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4-4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg></button>
            <div id="guest-language-menu" class="absolute right-0 mt-2 hidden w-20 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                @foreach (['fr' => 'FR', 'en' => 'EN'] as $locale => $label)
                    <form method="POST" action="{{ route('language.switch', $locale) }}">@csrf<button type="submit" class="w-full px-3 py-1.5 text-left text-xs hover:bg-emerald-50 hover:text-emerald-800">{{ $label }}</button></form>
                @endforeach
            </div>
        </div>
        <style>#guest-language-switcher>button{padding:.65rem 1rem;font-size:.95rem;font-weight:600;color:#065f46;background:#fff;border:1px solid #a7f3d0;border-radius:.6rem;box-shadow:0 2px 5px rgba(0,0,0,.12)}#guest-language-switcher>button svg{width:1rem;height:1rem}#guest-language-menu{width:6.5rem}</style>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/" class="flex flex-col items-center gap-2">
                    <img src="{{ asset('images/gut-logo.png') }}" alt="GUT Center" class="h-24 w-24 object-contain sm:h-28 sm:w-28" />
                    <span class="font-semibold text-emerald-800">GUT Center</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
        @if (session('status'))
            <div id="guest-status-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl"><h2 class="text-lg font-semibold text-emerald-800">{{ __('Action successful') }}</h2><p class="mt-2 text-gray-700">{{ session('status') }}</p><button type="button" onclick="document.getElementById('guest-status-modal').remove()" class="mt-5 rounded-lg bg-emerald-700 px-5 py-2 text-white">{{ __('OK') }}</button></div></div>
        @endif
        @if ((session('error') || $errors->any()) && $errors->first('email') !== __('Your account has been suspended. Contact the Manager.'))
            <div id="guest-error-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"><div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl"><h2 class="text-lg font-semibold text-red-800">{{ __('Action not completed') }}</h2><p class="mt-2 text-gray-700">{{ session('error') ?: $errors->first() }}</p><button type="button" onclick="document.getElementById('guest-error-modal').remove()" class="mt-5 rounded-lg bg-gray-700 px-5 py-2 text-white">{{ __('OK') }}</button></div></div>
        @endif
        <script>
            document.querySelectorAll('input[type="password"]').forEach(function (input) {
                const parent = input.parentElement;
                parent.classList.add('relative'); input.classList.add('pr-20');
                const button = document.createElement('button'); button.type = 'button'; button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z"/><circle cx="12" cy="12" r="3" stroke-width="2"/></svg>'; button.title = @json(__('Show password')); button.setAttribute('aria-label', button.title);
                button.className = 'absolute right-2 top-1/2 -translate-y-1/2 text-emerald-700';
                button.onclick = function () { const visible = input.type === 'text'; input.type = visible ? 'password' : 'text'; button.title = visible ? @json(__('Show password')) : @json(__('Hide password')); button.setAttribute('aria-label', button.title); };
                parent.appendChild(button);
            });
        </script>
    </body>
</html>
