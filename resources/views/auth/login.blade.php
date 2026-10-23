@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<style>
    .login-hero-bg {
        /*
         * Atur posisi & zoom background di sini:
         * --login-bg-x     : geser kiri/kanan (0% = kiri, 50% = tengah, 100% = kanan)
         * --login-bg-y     : geser atas/bawah (0% = atas, 50% = tengah, 100% = bawah)
         * --login-bg-size  : zoom tetap berbasis cover (100% = cover normal, 80% = kecil, 120% = besar)
         */
        --login-bg-x: 50%;
        --login-bg-y: 50%;
        --login-bg-size: 100%;
        --login-bg-overlay: linear-gradient(rgba(15, 15, 15, 0.45), rgba(15, 15, 15, 0.55));
        position: relative;
        isolation: isolate;
        background-color: #171717;
        overflow: hidden;
        margin-left: -25px;
    }
    .login-hero-bg::before,
    .login-hero-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
    }
    .login-hero-bg::before {
        z-index: 0;
        background-image: url('{{ asset('images/landing/bg-bengkel.jpg') }}');
        background-size: cover;
        background-position: var(--login-bg-x) var(--login-bg-y);
        background-repeat: no-repeat;
        transform: scale(calc(var(--login-bg-size) / 100%));
        transform-origin: var(--login-bg-x) var(--login-bg-y);
    }
    .login-hero-bg::after {
        z-index: 1;
        background-image: var(--login-bg-overlay);
    }
    .login-hero-bg > * {
        position: relative;
        z-index: 2;
    }
</style>
<div class="login-hero-bg relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8">

    {{-- Central white card --}}
    <div class="relative z-10 w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl">
        <div class="flex flex-col lg:flex-row">
            {{-- Left: SVG illustration (Proline-style) --}}
            <div class="hidden min-h-[420px] flex-1 items-center justify-center bg-gray-50/50 p-8 lg:flex" role="presentation">
                <img src="{{ asset('images/svgs/sign-in-illustration.png') }}" alt="" class="h-full max-h-80 w-full object-contain">
            </div>

            {{-- Right: Login form --}}
            <div class="flex flex-1 flex-col justify-center p-8 sm:p-10 lg:p-12">
                {{-- Logo 77racing di atas form (seperti Proline) --}}
                <div class="mb-8 flex flex-col items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="77 Racing Store" class="h-12 w-auto object-contain lg:h-14">
                </div>

                {{-- Error --}}
                @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-100 bg-red-50 p-4">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ $errors->first() }}</p>
                </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="sr-only">Email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </span>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                placeholder="nama@email.com"
                                class="block w-full rounded-xl border border-gray-200 bg-gray-50/80 py-3 pl-11 pr-4 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/40 @error('email') border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            >
                        </div>
                    </div>

                    {{-- Password --}}
                    <div x-data="{ show: false }">
                        <label for="password" class="sr-only">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </span>
                            <input
                                :type="show ? 'text' : 'password'"
                                id="password"
                                name="password"
                                required
                                placeholder="Minimal 6 karakter"
                                class="block w-full rounded-xl border border-gray-200 bg-gray-50/80 py-3 pl-11 pr-12 text-sm text-gray-900 placeholder-gray-400 transition-all focus:border-brand focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/40 @error('password') border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20 @enderror"
                            >
                            <button type="button" @click="show = !show" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-600">
                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember" value="1" @checked(old('remember', true)) class="h-4 w-4 rounded border-gray-300 text-brand accent-brand focus:ring-brand">
                        <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-brand px-4 py-3.5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 transition-all hover:bg-brand-hover hover:shadow-brand/40 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 active:scale-[0.99]">
                        Masuk
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-400">
                    &copy; {{ date('Y') }} 77racing. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
