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
                    <div class="hidden md:flex items-center space-x-4">
                        <a href="/dashboard" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 {{ request()->is('dashboard') ? 'bg-gray-700 text-white' : '' }}">Dashboard</a>
                        
                        <div class="relative group">
                            <button class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 flex items-center gap-1">
                                Faction
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <a href="/members" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('members') ? 'bg-gray-700 text-white' : '' }}">Members</a>
                                <a href="/wars" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('wars*') ? 'bg-gray-700 text-white' : '' }}">Ranked Wars</a>
                            </div>
                        </div>
                        
                        <div class="relative group">
                            <button class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 flex items-center gap-1">
                                Tools
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                            <div class="absolute left-0 mt-2 w-48 bg-gray-800 border border-gray-700 rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-50">
                                <a href="/gym" class="block px-4 py-2 text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->is('gym') ? 'bg-gray-700 text-white' : '' }}">Gym Assistant</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="/settings" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Settings</a>
                        @if(auth()->user()->is_admin)
                            <a href="/admin" class="px-3 py-2 rounded-md text-purple-300 hover:text-white hover:bg-gray-700">Admin</a>
                        @endif
                        <form action="/logout" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="/login" class="px-3 py-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>
