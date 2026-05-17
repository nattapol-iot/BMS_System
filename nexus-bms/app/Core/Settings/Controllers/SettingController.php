<?php
namespace App\Core\Settings\Controllers;

use App\Core\Settings\Models\SystemSetting;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->pluck('value', 'key')->all();
        $backups = BackupController::listBackups();
        return view('settings.index', compact('settings', 'backups'));
    }

    public function update(Request $request)
    {
        // Accept all submitted fields (each tab posts a subset)
        $exclude = ['_token', '_method', 'tab'];
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $exclude, true)) continue;
            SystemSetting::set($key, is_array($value) ? json_encode($value) : (string) $value);
        }

        return redirect()->route('settings.index')->with('success','Settings saved successfully.');
    }
}
