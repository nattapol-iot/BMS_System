<?php
namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Alarm;
use App\Models\Building;
use Illuminate\Support\Facades\Cache;

class LayoutComposer
{
    public function compose(View $view): void
    {
        $userId = auth()->id() ?? 0;

        $activeAlarms = Cache::remember("nav.active_alarms.{$userId}", 30, function () {
            return Alarm::where('status', 'active')->count();
        });

        $recentAlarms = Cache::remember("nav.recent_alarms.{$userId}", 30, function () {
            return Alarm::with('building')
                ->where('status', 'active')
                ->latest('triggered_at')
                ->limit(4)
                ->get();
        });

        $navBuildings = Cache::remember("nav.buildings.{$userId}", 120, function () {
            return Building::where('status', 'active')->limit(6)->get();
        });

        $view->with(compact('activeAlarms', 'recentAlarms', 'navBuildings'));
    }
}
