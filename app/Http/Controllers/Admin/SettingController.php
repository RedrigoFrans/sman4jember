<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('group', '!=', 'general')
            ->where('group', '!=', 'reservasi')
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return Inertia::render('Admin/Settings/Index', ['settings' => $settings]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($request->settings as $item) {
            Setting::set($item['key'], $item['value']);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}