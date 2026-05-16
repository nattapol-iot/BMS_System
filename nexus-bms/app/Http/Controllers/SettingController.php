<?php
namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($data['settings'] as $key => $value) {
            $group = $request->input("groups.{$key}", 'general');
            SystemSetting::set($key, $value, $group);
        }

        return redirect()->route('settings.index')->with('success','Settings saved successfully.');
    }
}
