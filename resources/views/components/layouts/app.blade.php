<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>{{ isset($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>
    <link
        rel="icon"
        href="/favicon.svg"
        type="image/svg+xml"
    >
    <link
        rel="icon"
        href="/favicon.ico"
        sizes="any"
    >
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-100 antialiased">

    <div
        x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
        class="flex h-screen overflow-hidden"
    >

        {{-- Sidebar --}}
        <aside
            class="flex shrink-0 flex-col border-r border-slate-800 bg-slate-900 transition-all duration-200"
            :class="sidebarCollapsed ? 'w-16' : 'w-56'"
        >
            <div class="flex h-16 items-center justify-center border-b border-slate-800 px-4">
                <a
                    href="{{ route('dashboard') }}"
                    x-show="!sidebarCollapsed"
                    x-cloak
                >
                    @if (config('security.branding.logo'))
                        <img
                            src="{{ asset(config('security.branding.logo')) }}"
                            alt="{{ config('security.branding.logo_alt') }}"
                            class="h-7 max-w-[140px] object-contain opacity-50"
                        />
                    @else
                        <span class="text-sm font-semibold tracking-wide text-slate-300">
                            {{ config('app.name') }}
                        </span>
                    @endif
                </a>
            </div>
            <nav class="flex-1 space-y-1 px-3 py-4">
                <x-nav-link
                    route-name="dashboard"
                    label="Services"
                >
                    <x-slot:icon>
                        <svg
                            class="size-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                        >
                            <rect
                                x="3"
                                y="3"
                                width="7"
                                height="7"
                                rx="1"
                            />
                            <rect
                                x="14"
                                y="3"
                                width="7"
                                height="7"
                                rx="1"
                            />
                            <rect
                                x="3"
                                y="14"
                                width="7"
                                height="7"
                                rx="1"
                            />
                            <rect
                                x="14"
                                y="14"
                                width="7"
                                height="7"
                                rx="1"
                            />
                        </svg>
                    </x-slot:icon>
                </x-nav-link>

                <x-nav-link
                    route-name="findings"
                    label="Findings"
                >
                    <x-slot:icon>
                        <svg
                            class="size-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                        >
                            <path
                                d="M9 12h6"
                                stroke-linecap="round"
                            />
                            <path
                                d="M9 16h6"
                                stroke-linecap="round"
                            />
                            <path
                                d="M5 8h14"
                                stroke-linecap="round"
                            />
                            <rect
                                x="4"
                                y="4"
                                width="16"
                                height="16"
                                rx="2"
                            />
                        </svg>
                    </x-slot:icon>
                </x-nav-link>

                <x-nav-link
                    route-name="trends"
                    label="Trend Analysis"
                >
                    <x-slot:icon>
                        <svg
                            class="size-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.75"
                        >
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                        </svg>
                    </x-slot:icon>
                </x-nav-link>

                @can('users.manage')
                    <x-nav-link
                        route-name="admin.users"
                        label="User Management"
                    >
                        <x-slot:icon>
                            <svg
                                class="size-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <circle
                                    cx="9"
                                    cy="7"
                                    r="4"
                                />
                                <path
                                    d="M22 21v-2a4 4 0 0 0-3-3.87"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="M16 3.13a4 4 0 0 1 0 7.75"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </x-slot:icon>
                    </x-nav-link>
                @endcan

                @can('settings.manage')
                    <x-nav-link
                        route-name="admin.settings"
                        label="Settings"
                    >
                        <x-slot:icon>
                            <svg
                                class="size-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />
                                <path
                                    d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"
                                />
                            </svg>
                        </x-slot:icon>
                    </x-nav-link>
                @endcan

                @can('view.logs')
                    <x-nav-link
                        route-name="admin.audit-log"
                        label="Audit Log"
                    >
                        <x-slot:icon>
                            <svg
                                class="size-4 shrink-0"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6M9 16h4"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 8h14"
                                />
                                <rect
                                    x="4"
                                    y="4"
                                    width="16"
                                    height="16"
                                    rx="2"
                                />
                                <circle
                                    cx="17"
                                    cy="17"
                                    r="3"
                                    fill="none"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17 15.5V17l1 1"
                                />
                            </svg>
                        </x-slot:icon>
                    </x-nav-link>
                @endcan

                <x-slot:icon>
                    <svg
                        class="size-4 shrink-0"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                    >
                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M11 8v3l2 2"
                        />
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 16.5l4 4"
                        />
                    </svg>
                </x-slot:icon>
                </x-nav-link>
            </nav>
            <div class="mt-auto border-t border-slate-800">
                @auth
                    {{-- Expanded: user name + collapse button op één rij, logout eronder --}}
                    <div
                        class="space-y-2 px-3 py-3"
                        x-show="!sidebarCollapsed"
                        x-cloak
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="min-w-0 truncate text-xs text-slate-400">
                                {{ session('azure_user_name', auth()->user()->name) }}
                            </span>
                            <button
                                type="button"
                                class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md border border-slate-700 text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200"
                                @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                                title="Collapse sidebar"
                                aria-label="Collapse sidebar"
                            >
                                <svg
                                    class="size-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 6l-6 6 6 6"
                                    />
                                </svg>
                            </button>
                        </div>
                        <form
                            method="POST"
                            action="{{ route('auth.logout') }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex w-full cursor-pointer items-center justify-center rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-300 transition-colors hover:bg-slate-800"
                            >
                                Logout
                            </button>
                        </form>
                    </div>

                    {{-- Collapsed: collapse button + logout icon gestapeld --}}
                    <div
                        class="flex flex-col items-center gap-2 px-2 py-3"
                        x-show="sidebarCollapsed"
                        x-cloak
                    >
                        <button
                            type="button"
                            class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md border border-slate-700 text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-200"
                            @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
                            title="Expand sidebar"
                            aria-label="Expand sidebar"
                        >
                            <svg
                                class="size-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.75"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 6l6 6-6 6"
                                />
                            </svg>
                        </button>
                        <form
                            method="POST"
                            action="{{ route('auth.logout') }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex size-8 cursor-pointer items-center justify-center rounded-md border border-slate-700 text-slate-300 transition-colors hover:bg-slate-800"
                                title="Logout"
                                aria-label="Logout"
                            >
                                <svg
                                    class="size-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.75"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16 17l5-5-5-5"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 12H9"
                                    />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

            {{-- Top nav --}}
            <header class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 bg-slate-900 px-6">
                <button
                    type="button"
                    @click="history.back()"
                    class="inline-flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-md border border-slate-700 text-slate-400 transition-colors hover:bg-slate-800 hover:text-slate-100"
                    title="Back"
                    aria-label="Back"
                >
                    <svg
                        class="size-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.75"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 18l-6-6 6-6"
                        />
                    </svg>
                </button>
                <h1 class="text-sm font-semibold text-slate-100">
                    {{ $title ?? 'Services' }}
                </h1>
            </header>

            {{-- Page content --}}
            <main class="flex-1 overflow-y-auto bg-slate-950 p-6">
                {{ $slot }}
            </main>

        </div>
    </div>

    @stack('scripts')
    @livewireScripts
</body>

</html>
