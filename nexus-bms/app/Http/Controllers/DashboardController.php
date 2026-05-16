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

        $yesterdayEnergy = EnergyLog::whereDate('logged_at', Carbon::yesterday())
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $energyTrend = $yesterdayEnergy > 0
            ? round((($todayEnergy - $yesterdayEnergy) / $yesterdayEnergy) * 100, 1)
            : 0;

        $currentMonthEnergy = EnergyLog::whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $previousMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();
        $previousMonthEnergy = EnergyLog::whereBetween('logged_at', [$previousMonthStart, $previousMonthEnd])
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $monthlyEnergyTrend = $previousMonthEnergy > 0
            ? round((($currentMonthEnergy - $previousMonthEnergy) / $previousMonthEnergy) * 100, 1)
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
        $utilityComparisonData = [
            'electricity' => $this->getUtilityComparisonData('electricity', 'kWh'),
            'water' => $this->getUtilityComparisonData('water', 'm³'),
        ];

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
            'todayEnergy', 'energyTrend', 'currentMonthEnergy', 'monthlyEnergyTrend', 'hvacOnline', 'hvacTotal',
            'waterToday', 'systemHealth'
        );

        return view('dashboard.index', compact(
            'stats', 'energyChartData', 'energyBreakdown', 'utilityComparisonData',
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

    private function getUtilityComparisonData(string $type, string $unit): array
    {
        $now = Carbon::now();
        $meters = EnergyMeter::with(['building', 'floor'])
            ->where('type', $type)
            ->orderBy('building_id')
            ->orderBy('floor_id')
            ->orderBy('name')
            ->get();

        $meterIds = $meters->pluck('id');
        $monthStart = $now->copy()->subDays(29)->startOfDay();
        $logsByMeter = $meterIds->isEmpty()
            ? collect()
            : EnergyLog::whereIn('meter_id', $meterIds)
                ->whereBetween('logged_at', [$monthStart, $now])
                ->get()
                ->groupBy('meter_id');

        $periods = [
            'day' => $this->buildUtilityPeriod($meters, $logsByMeter, 'day', $now->copy()->startOfDay(), $unit),
            'week' => $this->buildUtilityPeriod($meters, $logsByMeter, 'week', $now->copy()->subDays(6)->startOfDay(), $unit),
            'month' => $this->buildUtilityPeriod($meters, $logsByMeter, 'month', $monthStart, $unit),
        ];

        $floorCount = $meters->pluck('floor_id')->filter()->unique()->count();

        return [
            'unit' => $unit,
            'meterCount' => $meters->count(),
            'floorCount' => $floorCount,
            'periods' => $periods,
        ];
    }

    private function buildUtilityPeriod($meters, $logsByMeter, string $period, Carbon $start, string $unit): array
    {
        $keys = [];
        $categories = [];
        $cursor = $start->copy();
        $now = Carbon::now();

        if ($period === 'day') {
            for ($hour = 0; $hour < 24; $hour++) {
                $keys[] = sprintf('%02d', $hour);
                $categories[] = sprintf('%02d:00', $hour);
            }
        } else {
            while ($cursor <= $now) {
                $keys[] = $cursor->format('Y-m-d');
                $categories[] = $cursor->format('d M');
                $cursor->addDay();
            }
        }

        $series = $meters->map(function ($meter) use ($logsByMeter, $keys, $period, $start) {
            $meterLogs = $logsByMeter->get($meter->id, collect())
                ->filter(fn($log) => $log->logged_at >= $start);

            $valuesByKey = $meterLogs
                ->groupBy(fn($log) => $period === 'day'
                    ? $log->logged_at->format('H')
                    : $log->logged_at->format('Y-m-d'))
                ->map(fn($items) => round((float) $items->sum('value'), 2));

            return [
                'name' => $this->formatMeterLabel($meter),
                'data' => collect($keys)->map(fn($key) => $valuesByKey->get($key, 0))->values(),
            ];
        })->values();

        return [
            'categories' => $categories,
            'series' => $series,
            'unit' => $unit,
        ];
    }

    private function formatMeterLabel(EnergyMeter $meter): string
    {
        $building = $meter->building?->name ?? 'Building';
        $floor = $meter->floor
            ? 'F' . $meter->floor->floor_number
            : 'Main';

        return "{$building} {$floor}";
    }
}
