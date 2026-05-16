<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogActivity
{
    private array $ignoredRoutes = ['login', 'logout', 'lang.switch'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (Auth::check() && $request->isMethod('GET') && !$this->isIgnored($request)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'view',
                'module' => $this->getModule($request),
                'description' => "Visited: " . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => substr($request->userAgent() ?? '', 0, 200),
            ]);
        }

        return $response;
    }

    private function isIgnored(Request $request): bool
    {
        foreach ($this->ignoredRoutes as $route) {
            if ($request->routeIs($route)) return true;
        }
        return false;
    }

    private function getModule(Request $request): string
    {
        $segments = explode('/', trim($request->path(), '/'));
        return $segments[0] ?? 'general';
    }
}
