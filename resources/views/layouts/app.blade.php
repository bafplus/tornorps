<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TornOps')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-xl font-bold text-blue-400">TornOps</a>
                    <div class="hidden md:flex space-x-4">
                        <a href="/dashboard" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>
                        <a href="/members" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('members') ? 'bg-gray-700 text-white' : '' }}">Members</a>
                        <a href="/wars" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('wars*') ? 'bg-gray-700 text-white' : '' }}">Ranked Wars</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="/settings" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Settings</a>
                        @if(auth()->user()->is_admin)
                            <a href="/admin" class="px-3 py-2 rounded-md text-purple-300 hover:text-white hover:bg-gray-700">Admin</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
</body>
</html>
