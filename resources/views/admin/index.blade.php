@extends('layouts.app')

@section('title', 'Admin - TornOps')

@section('content')
<div class="space-y-6">
    <h1 class="text-3xl font-bold">Admin Panel</h1>

    @if(session('status'))
        <div class="bg-green-900/50 border border-green-700 text-green-400 px-4 py-3 rounded">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-900/50 border border-red-700 text-red-400 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-blue-400">API Settings</h2>
        <form action="/admin/settings" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-400 mb-2">Faction ID</label>
                    <input type="number" name="faction_id" value="{{ $settings->faction_id ?? '' }}" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">Torn API Key</label>
                    <input type="text" name="torn_api_key" value="{{ $settings->torn_api_key ?? '' }}" required
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-400 mb-2">FFScouter API Key (Optional)</label>
                    <input type="text" name="ffscouter_api_key" value="{{ $settings->ffscouter_api_key ?? '' }}"
                           class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white font-mono focus:outline-none focus:border-blue-500">
                </div>
                <div class="flex items-center">
                    <label class="flex items-center text-gray-400 cursor-pointer">
                        <input type="checkbox" name="auto_sync_enabled" value="1" {{ ($settings->auto_sync_enabled ?? false) ? 'checked' : '' }}
                               class="mr-2 w-5 h-5 rounded bg-gray-700 border-gray-600">
                        Enable Auto Sync
                    </label>
                </div>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded text-white">
                Save Settings
            </button>
        </form>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-purple-400">Users</h2>
        
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-300 mb-3">Create New User</h3>
            <form action="/admin/users" method="POST" class="flex flex-wrap gap-4 items-end">
                @csrf
                <div>
                    <input type="text" name="name" placeholder="Name" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="email" name="email" placeholder="Email" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="password" name="password" placeholder="Password" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <input type="password" name="password_confirmation" placeholder="Confirm" required
                           class="bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="flex items-center text-gray-400 cursor-pointer mr-4">
                        <input type="checkbox" name="is_admin" value="1" class="mr-2 w-4 h-4 rounded bg-gray-700 border-gray-600">
                        Admin
                    </label>
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white">
                    Create User
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-gray-400 bg-gray-700/50">
                        <th class="p-3">Name</th>
                        <th class="p-3">Email</th>
                        <th class="p-3">Role</th>
                        <th class="p-3">API Key</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-700/30">
                        <td class="p-3">{{ $user->name }}</td>
                        <td class="p-3 text-gray-400">{{ $user->email }}</td>
                        <td class="p-3">
                            @if($user->is_admin)
                                <span class="px-2 py-1 rounded text-xs bg-purple-900 text-purple-400">Admin</span>
                            @else
                                <span class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-400">User</span>
                            @endif
                        </td>
                        <td class="p-3 font-mono text-sm text-gray-400">
                            {{ $user->torn_api_key ? substr($user->torn_api_key, 0, 8).'...' : '-' }}
                        </td>
                        <td class="p-3 text-right">
                            <form action="/admin/users/{{ $user->id }}/toggle" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-blue-400 hover:text-blue-300 text-sm">
                                    {{ $user->is_admin ? 'Remove Admin' : 'Make Admin' }}
                                </button>
                            </form>
                            @if($user->id !== auth()->id())
                                <form action="/admin/users/{{ $user->id }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-300 text-sm ml-2"
                                            onclick="return confirm('Delete this user?')">
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-gray-300">Sync Commands</h2>
        <div class="flex flex-wrap gap-4">
            <form action="/admin/sync/factions" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">
                    Sync Faction Data
                </button>
            </form>
            <form action="/admin/sync/wars" method="POST">
                @csrf
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-white">
                    Sync War Data
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
