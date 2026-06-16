<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>Sign in — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/login.js'])
</head>

<body
    class="bg-grid flex min-h-screen items-center justify-center bg-slate-950"
    data-ec="{{ $errors->has('login') ? '0, 84%, 60%' : '217, 91%, 60%' }}"
    data-has-error="{{ $errors->has('login') ? '1' : '0' }}"
>

    {{-- Eyes background --}}
    <div
        id="eyes-bg"
        class="pointer-events-none fixed inset-0 overflow-hidden"
    ></div>

    {{-- Login card --}}
    <div class="relative z-10 flex w-full max-w-sm flex-col items-center px-8 text-center">

        {{-- Branding --}}
        <div
            class="{{ $errors->has('login') ? 'bg-red-500/10 border-red-500/20' : 'bg-blue-500/10 border-blue-500/20' }} mb-4 inline-flex items-center gap-2 rounded-full border px-3 py-1">
            <div
                class="{{ $errors->has('login') ? 'bg-red-400' : 'bg-blue-400' }} h-1.5 w-1.5 animate-pulse rounded-full">
            </div>
            <span
                class="{{ $errors->has('login') ? 'text-red-400' : 'text-blue-400' }} text-xs font-medium">{{ $errors->has('login') ? 'Access denied' : 'Security monitoring active' }}</span>
        </div>

        <h1 class="mb-3 text-5xl font-bold text-white">
            {{ config('app.name') }}
        </h1>

        <p class="mb-8 text-sm leading-relaxed text-slate-400">
            Centralized overview of security scans<br>
            across all repositories — automatically updated via CI/CD.
        </p>

        {{-- Error --}}
        @error('login')
            <div class="mb-4 w-full rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                {{ $message }}
            </div>
        @enderror

        @if (config('integrations.auth.provider') === 'azure')
            <a
                href="{{ route('auth.azure.redirect', ['provider' => 'azure']) }}"
                class="glow-btn flex w-full items-center justify-center gap-3 rounded-xl bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-500"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 23 23"
                    class="h-4 w-4 shrink-0"
                >
                    <path
                        fill="#f3f3f3"
                        d="M0 0h23v23H0z"
                    />
                    <path
                        fill="#f35325"
                        d="M1 1h10v10H1z"
                    />
                    <path
                        fill="#81bc06"
                        d="M12 1h10v10H12z"
                    />
                    <path
                        fill="#05a6f0"
                        d="M1 12h10v10H1z"
                    />
                    <path
                        fill="#ffba08"
                        d="M12 12h10v10H12z"
                    />
                </svg>
                Sign in with Microsoft
            </a>
        @elseif (config('integrations.auth.provider') === 'local')
            <form
                method="POST"
                action="{{ route('auth.local.login') }}"
                class="flex w-full flex-col gap-3 text-left"
            >
                @csrf

                <div>
                    <label
                        for="email"
                        class="mb-1 block text-xs font-medium text-slate-400"
                    >Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-1 block text-xs font-medium text-slate-400"
                    >Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none"
                    >
                </div>

                <label class="flex items-center gap-2 text-xs text-slate-400">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        class="rounded border-slate-700 bg-slate-900"
                    >
                    Remember me
                </label>

                <button
                    type="submit"
                    class="glow-btn mt-1 flex w-full items-center justify-center gap-3 rounded-xl bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors duration-150 hover:bg-blue-500"
                >
                    Sign in
                </button>
            </form>
        @else
            <div class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3 text-sm text-slate-400">
                Configure an authentication provider to enable sign-in.
            </div>
        @endif

        @if (count(config('security.users.allowed_email_domains', [])) > 0)
            <p class="mt-6 text-xs text-slate-600">
                Only accessible to authorized domains
            </p>
        @endif

        @if (config('security.branding.logo'))
            <img
                src="{{ asset(config('security.branding.logo')) }}"
                alt="{{ config('security.branding.logo_alt') }}"
                class="mt-10 h-6 opacity-30"
            >
        @endif

    </div>

</body>

</html>
