<?php
namespace App\Core\AuditLog\Controllers;

use App\Core\AuditLog\Models\ActivityLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $modules = ActivityLog::distinct()->pluck('module')->filter()->values();
        $actions = ActivityLog::distinct()->pluck('action')->filter()->values();

        return view('logs.index', compact('logs','modules','actions'));
    }
}
