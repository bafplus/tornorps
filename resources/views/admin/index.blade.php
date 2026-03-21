@extends('layouts.app')

@section('title', 'Admin - TornOps')

@section('content')
<div class="space-y-6">
    <h1 class="text-3xl font-bold">Admin Panel</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 class="text-xl font-semibold mb-4 text-blue-400">API Instellingen</h2>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-400">Faction ID:</dt>
                    <dd class="font-mono">{{ $settings->faction_id ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Torn API Key:</dt>
                    <dd class="font-mono text-sm">
                        @if($settings->torn_api_key)
                            <span class="text-green-400">Geconfigureerd</span>
                        @else
                            <span class="text-red-400">Niet geconfigureerd</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">FFScouter API Key:</dt>
                    <dd class="font-mono text-sm">
                        @if($settings->ffscouter_api_key)
                            <span class="text-green-400">Geconfigureerd</span>
                        @else
                            <span class="text-yellow-400">Optioneel</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <a href="/admin/api" class="mt-4 inline-block text-blue-400 hover:text-blue-300">Bewerken →</a>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 class="text-xl font-semibold mb-4 text-purple-400">Sync Status</h2>
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-gray-400">Auto Sync:</dt>
                    <dd>
                        @if($settings->auto_sync_enabled)
                            <span class="text-green-400">Ingeschakeld</span>
                        @else
                            <span class="text-red-400">Uitgeschakeld</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-400">Laatste Sync:</dt>
                    <dd>{{ $settings->updated_at ? $settings->updated_at->diffForHumans() : 'Nooit' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-gray-300">Gebruikers</h2>
        <p class="text-gray-400">Gebruikersbeheer komt hier.</p>
    </div>

    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 class="text-xl font-semibold mb-4 text-gray-300">Sync Commando's</h2>
        <div class="flex flex-wrap gap-4">
            <form action="/admin/sync/factions" method="POST">
                @csrf
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">
                    Sync Faction Data
                </button>
            </form>
            <form action="/admin/sync/wars" method="POST">
                @csrf
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded">
                    Sync War Data
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
