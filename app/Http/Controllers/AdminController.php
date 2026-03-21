<?php

namespace App\Http\Controllers;

use App\Models\FactionSettings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $users = User::orderBy('is_admin', 'desc')->orderBy('name')->get();
        
        return view('admin.index', compact('settings', 'users'));
    }

    public function updateFactionSettings(Request $request)
    {
        $request->validate([
            'faction_id' => ['required', 'integer'],
            'torn_api_key' => ['required', 'string', 'max:100'],
            'ffscouter_api_key' => ['nullable', 'string', 'max:100'],
            'auto_sync_enabled' => ['boolean'],
        ]);

        $settings = FactionSettings::first();
        $settings->update([
            'faction_id' => $request->faction_id,
            'torn_api_key' => $request->torn_api_key,
            'ffscouter_api_key' => $request->ffscouter_api_key,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
        ]);

        return back()->with('status', 'Faction settings updated successfully.');
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_admin' => ['boolean'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin'),
        ]);

        return back()->with('status', 'User created successfully.');
    }

    public function toggleAdmin(User $user)
    {
        $user->update(['is_admin' => !$user->is_admin]);
        return back()->with('status', 'Admin status toggled.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }
        $user->delete();
        return back()->with('status', 'User deleted.');
    }
}
