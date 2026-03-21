<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FactionSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SetupController extends Controller
{
    public function index()
    {
        $settings = FactionSettings::first();
        $hasAdmin = User::where('is_admin', true)->exists();

        if ($settings && $hasAdmin) {
            return redirect('/');
        }

        return view('setup.index', [
            'settings' => $settings,
            'hasAdmin' => $hasAdmin,
            'step' => $settings ? 2 : 1,
        ]);
    }

    public function store(Request $request)
    {
        $step = $request->input('step', 1);

        if ($step == 1) {
            return $this->setupApiKey($request);
        }

        if ($step == 2) {
            return $this->createAdmin($request);
        }

        return redirect('/setup');
    }

    protected function setupApiKey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faction_id' => 'required|numeric',
            'torn_api_key' => 'required|string|min:16',
            'ffscouter_api_key' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('step', 1);
        }

        FactionSettings::updateOrCreate(
            ['id' => 1],
            [
                'faction_id' => $request->input('faction_id'),
                'torn_api_key' => $request->input('torn_api_key'),
                'ffscouter_api_key' => $request->input('ffscouter_api_key'),
                'auto_sync_enabled' => true,
            ]
        );

        return redirect('/setup?step=2')->with('success', 'API keys configured successfully');
    }

    protected function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:8|confirmed',
            'torn_player_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('step', 2);
        }

        User::create([
            'name' => $request->input('admin_name'),
            'email' => $request->input('admin_email'),
            'password' => Hash::make($request->input('admin_password')),
            'torn_player_id' => $request->input('torn_player_id'),
            'is_admin' => true,
        ]);

        return redirect('/')->with('success', 'Admin account created successfully!');
    }
}
