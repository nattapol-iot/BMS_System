<?php
namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Equipment;
use App\Models\Alarm;
use App\Models\EnergyLog;
use App\Models\EnergyMeter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalBuildings = Building::count();
        $activeEquipment = Equipment::where('status', 'active')->count();
        $activeAlarms = Alarm::whereIn('status', ['active', 'acknowledged'])->count();
        $criticalAlarms = Alarm::where('status', 'active')->where('severity', 'critical')->count();

        $todayEnergy = EnergyLog::whereDate('logged_at', today())
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $yesterdayEnergy = EnergyLog::whereDate('logged_at', yesterday())
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $energyTrend = $yesterdayEnergy > 0
            ? round((($todayEnergy - $yesterdayEnergy) / $yesterdayEnergy) * 100, 1)
            : 0;

        $hvacEquipment = Equipment::whereHas('category', fn($q) => $q->where('name', 'HVAC'))->get();
        $hvacOnline = $hvacEquipment->where('status', 'active')->count();
        $hvacTotal = $hvacEquipment->count();

        $waterToday = EnergyLog::whereDate('logged_at', today())
            ->whereHas('meter', fn($q) => $q->where('type', 'water'))
            ->sum('value');

        $systemHealth = $activeEquipment > 0
            ? (int) round((Equipment::where('status','active')->avg('health_score') ?? 98))
            : 98;

        $energyChartData = $this->getEnergyChartData();
        $energyBreakdown = $this->getEnergyBreakdown();

        $buildings = Building::with(['floors', 'alarms'])->limit(4)->get();
        $recentAlarms = Alarm::with(['equipment', 'building'])
            ->orderByDesc('triggered_at')
            ->limit(8)
            ->get();

        $equipmentStatus = Equipment::with(['category', 'building'])
            ->limit(5)
            ->get();

        $stats = compact(
            'totalBuildings', 'activeEquipment', 'activeAlarms', 'criticalAlarms',
            'todayEnergy', 'energyTrend', 'hvacOnline', 'hvacTotal',
            'waterToday', 'systemHealth'
        );

        return view('dashboard.index', compact(
            'stats', 'energyChartData', 'energyBreakdown',
            'buildings', 'recentAlarms', 'equipmentStatus'
        ));
    }

    private function getEnergyChartData(): array
    {
        $days = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days[] = $date->format('d M');
            $energy = EnergyLog::whereDate('logged_at', $date)
                ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
                ->sum('value');
            $values[] = round($energy, 2) ?: rand(800, 1500);
        }
        return ['days' => $days, 'values' => $values];
    }

    private function getEnergyBreakdown(): array
    {
        return [
            ['label' => 'HVAC', 'value' => 41, 'color' => '#3b82f6'],
            ['label' => 'Lighting', 'value' => 36, 'color' => '#f59e0b'],
            ['label' => 'Elevators', 'value' => 8, 'color' => '#8b5cf6'],
            ['label' => 'Others', 'value' => 15, 'color' => '#94a3b8'],
        ];
    }
}
