<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TornOps Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="max-w-xl w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-400 mb-2">TornOps</h1>
            <p class="text-gray-400">Setup Wizard</p>
        </div>

        @if(session('success'))
            <div class="bg-green-900/50 border border-green-500 text-green-400 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-900/50 border border-red-500 text-red-400 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-gray-800 rounded-lg shadow-xl p-8">
            @if($step == 1)
                <h2 class="text-2xl font-semibold mb-6 text-blue-400">Step 1: API Configuration</h2>
                <form action="/setup" method="POST">
                    @csrf
                    <input type="hidden" name="step" value="1">
                    
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="faction_id">
                            Faction ID
                        </label>
                        <input type="number" name="faction_id" id="faction_id" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="55772" required>
                        <p class="text-gray-500 text-sm mt-1">Your Torn faction ID</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="torn_api_key">
                            Torn API Key
                        </label>
                        <input type="text" name="torn_api_key" id="torn_api_key" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="Your Torn API key" required minlength="16">
                        <p class="text-gray-500 text-sm mt-1">
                            Get your key at <a href="https://www.torn.com/preferences.php#tab=api" target="_blank" class="text-blue-400 hover:underline">Torn Preferences</a>
                        </p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="ffscouter_api_key">
                            FFScouter API Key <span class="text-gray-500">(optional)</span>
                        </label>
                        <input type="text" name="ffscouter_api_key" id="ffscouter_api_key" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="FFScouter API key">
                        <p class="text-gray-500 text-sm mt-1">
                            Get your key at <a href="https://ffscouter.com/" target="_blank" class="text-blue-400 hover:underline">FFScouter</a>
                        </p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded transition">
                        Next Step →
                    </button>
                </form>

            @else
                <h2 class="text-2xl font-semibold mb-6 text-blue-400">Step 2: Admin Account</h2>
                <form action="/setup" method="POST">
                    @csrf
                    <input type="hidden" name="step" value="2">
                    
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="admin_name">
                            Admin Name
                        </label>
                        <input type="text" name="admin_name" id="admin_name" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="Your name" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="admin_email">
                            Admin Email
                        </label>
                        <input type="email" name="admin_email" id="admin_email" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="admin@example.com" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="admin_password">
                            Password
                        </label>
                        <input type="password" name="admin_password" id="admin_password" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="Min 8 characters" required minlength="8">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="admin_password_confirmation">
                            Confirm Password
                        </label>
                        <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="Repeat password" required>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2" for="torn_player_id">
                            Your Torn Player ID <span class="text-gray-500">(optional)</span>
                        </label>
                        <input type="number" name="torn_player_id" id="torn_player_id" 
                               class="w-full bg-gray-700 border border-gray-600 rounded px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
                               placeholder="Your Torn player ID">
                    </div>

                    <button type="submit" 
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded transition">
                        Complete Setup
                    </button>
                </form>
            @endif
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            TornOps - Torn City Faction Portal
        </p>
    </div>
</body>
</html>
