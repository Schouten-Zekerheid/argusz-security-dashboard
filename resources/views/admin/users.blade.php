<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >
    <title>User Management — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50">
    <header class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-4">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('dashboard') }}"
                class="text-sm text-gray-400 transition-colors hover:text-gray-600"
            >&larr;
                Dashboard</a>
            <span class="text-gray-300">|</span>
            <span class="font-semibold text-gray-900">User Management</span>
        </div>
        <div class="flex items-center gap-4 text-sm text-gray-500">
            <span>{{ $userName }}</span>
            @if ($userRole)
                <span class="text-xs text-gray-400">({{ $userRole }})</span>
            @endif
            <form
                method="POST"
                action="{{ route('auth.logout') }}"
            >
                @csrf
                <button
                    type="submit"
                    class="text-gray-400 transition-colors hover:text-gray-600"
                >Logout</button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-4xl p-6">
        <livewire:admin.user-management />
    </main>
</body>

</html>
