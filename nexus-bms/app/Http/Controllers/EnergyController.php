<?php
namespace App\Http\Controllers;

use App\Models\EnergyMeter;
use App\Models\EnergyLog;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Core\Settings\Models\SystemSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnergyController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate   = $request->filled('end_date')   ? Carbon::parse($request->end_date)   : Carbon::now();
        $buildingId = $request->building_id;

        $buildings = Building::where('status', 'active')->get();
        $selectedBuilding = $buildingId ? Building::find($buildingId) : null;

        // === Rates from system_settings (admin-configurable) ===
        $electricityRate = (float) SystemSetting::get('electricity_rate', 4.50);
        $waterRate       = (float) SystemSetting::get('water_rate', 25.00);
        $solarFeedinRate = (float) SystemSetting::get('solar_feedin_rate', 2.20);
        $currencySymbol  = SystemSetting::get('currency_symbol', '฿');

        // === Meter ID groups ===
        $elecMeterIds  = EnergyMeter::where('type', 'electricity')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))->pluck('id');
        $waterMeterIds = EnergyMeter::where('type', 'water')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))->pluck('id');
        $solarMeterIds = EnergyMeter::where('type', 'solar')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))->pluck('id');

        // === KPI numbers ===
        $todayKwh = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->whereDate('logged_at', Carbon::today())->sum('value');
        $monthKwh = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');
        $peakDemand = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->max('peak_demand') ?? 0;

        $waterConsumption = (float) EnergyLog::whereIn('meter_id', $waterMeterIds)
            ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');
        $avgPowerFactor = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->avg('power_factor') ?? 0.95;
        $carbonEmission = round($monthKwh * 0.4864 / 1000, 1);

        $costEstimate    = round($monthKwh * $electricityRate, 0);
        $electricityCost = $costEstimate;
        $totalConsumption = $monthKwh;

        // === Meter table data ===
        $meters = EnergyMeter::with(['floor', 'building', 'equipment'])
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->get()
            ->map(function ($m) {
                $m->today_kwh = (float) EnergyLog::where('meter_id', $m->id)
                    ->whereDate('logged_at', Carbon::today())->sum('value');
                $m->monthly_kwh = (float) EnergyLog::where('meter_id', $m->id)
                    ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');
                return $m;
            });

        // === Solar from real solar meters (fallback to sin curve if none) ===
        [$solarHourly, $solarDaily] = $this->getRealSolarSeries($solarMeterIds);
        $solarTodayKwh = round((float) EnergyLog::whereIn('meter_id', $solarMeterIds)
            ->whereDate('logged_at', Carbon::today())->sum('value'), 1);
        $solarMonthKwh = round((float) EnergyLog::whereIn('meter_id', $solarMeterIds)
            ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value'), 1);
        $solarCoverage = $todayKwh > 0 ? round(($solarTodayKwh / $todayKwh) * 100, 1) : 0;
        $gridImportToday = round(max($todayKwh - $solarTodayKwh, 0), 1);

        // === Analytical: by building ===
        $byBuildingRaw = Building::where('status', 'active')->get()->map(function ($b) {
            $kwh = (float) EnergyLog::whereHas('meter', fn($q) => $q->where('building_id', $b->id)->where('type','electricity'))
                ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');
            return ['id' => $b->id, 'name' => $b->name, 'kwh' => round($kwh, 1)];
        })->sortByDesc('kwh')->values()->all();
        $byBuildingTotal = array_sum(array_column($byBuildingRaw, 'kwh')) ?: 1;
        $byBuilding = array_map(fn($b) => array_merge($b, ['pct' => round($b['kwh'] / $byBuildingTotal * 100, 1)]), $byBuildingRaw);

        // === By floor (when building selected) ===
        $byFloor = [];
        if ($selectedBuilding) {
            $byFloorRaw = Floor::where('building_id', $selectedBuilding->id)->orderBy('floor_number')->get()->map(function ($f) {
                $kwh = (float) EnergyLog::whereHas('meter', fn($q) => $q->where('floor_id', $f->id)->where('type', 'electricity'))
                    ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');
                return ['id' => $f->id, 'name' => $f->name ?? ('F'.$f->floor_number), 'kwh' => round($kwh, 1)];
            })->filter(fn($f) => $f['kwh'] > 0)->values()->all();
            $byFloorTotal = array_sum(array_column($byFloorRaw, 'kwh')) ?: 1;
            $byFloor = array_map(fn($f) => array_merge($f, ['pct' => round($f['kwh'] / $byFloorTotal * 100, 1)]), $byFloorRaw);
        }

        // === Top consumers by category (REAL — joins meter → equipment → category) ===
        $byCategory = $this->buildTopByCategory($buildingId);

        // === Month-over-month + forecast ===
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd   = $lastMonthStart->copy()->endOfMonth();
        $lastMonthKwh = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->whereBetween('logged_at', [$lastMonthStart, $lastMonthEnd])->sum('value');

        $daysIntoMonth = max(Carbon::now()->day, 1);
        $daysInMonth = Carbon::now()->daysInMonth;
        $projectedThisMonth = $daysIntoMonth > 0 ? ($monthKwh / $daysIntoMonth) * $daysInMonth : 0;

        $last14DayAvg = (float) EnergyLog::whereIn('meter_id', $elecMeterIds)
            ->where('logged_at', '>=', Carbon::now()->subDays(14))->sum('value') / 14;
        $forecastNextMonth = round($last14DayAvg * Carbon::now()->addMonthNoOverflow()->daysInMonth, 1);

        $monthlyCompare = $this->buildYearlyCompare($elecMeterIds, $monthKwh, $projectedThisMonth, $last14DayAvg);
        $vsLastMonth = $lastMonthKwh > 0 ? round(($monthKwh - $lastMonthKwh) / $lastMonthKwh * 100, 1) : 0;

        // === 30-day trend (real) ===
        $trendData = $this->getRealTrendData($elecMeterIds, $startDate, $endDate);

        // === Hourly today (real) ===
        $hourlyToday = $this->getRealHourlyToday($elecMeterIds);
        $hourlyData = $hourlyToday;

        // === Solar comparison object (used by chart) ===
        $solarData = [
            'today' => [
                'categories'   => $hourlyToday['hours'],
                'consumption'  => $hourlyToday['values'],
                'production'   => $solarHourly,
                'gridImport'   => array_map(fn($c, $p) => round(max($c - $p, 0), 1), $hourlyToday['values'], $solarHourly),
            ],
            'month' => [
                'categories'  => $trendData['days'],
                'consumption' => $trendData['current'],
                'production'  => $solarDaily,
                'gridImport'  => array_map(fn($c, $p) => round(max($c - $p, 0), 1), $trendData['current'], $solarDaily),
            ],
        ];

        // === Backwards-compat aliases ===
        $energyBreakdown = array_map(fn($c) => [
            'label' => $c['name'], 'value' => $c['kwh'], 'pct' => $c['pct'], 'color' => $c['color'] ?? '#6b7280',
        ], array_slice($byCategory, 0, 5));

        $topConsumers = array_slice(array_map(fn($b) => [
            'system' => $b['name'], 'building' => '—', 'consumption' => $b['kwh'], 'pct' => $b['pct'],
        ], $byBuilding), 0, 6);

        return view('energy.index', compact(
            'buildings', 'selectedBuilding', 'meters', 'startDate', 'endDate', 'buildingId',
            'todayKwh', 'monthKwh', 'peakDemand', 'costEstimate',
            'solarData', 'solarTodayKwh', 'solarMonthKwh', 'solarCoverage', 'gridImportToday',
            'totalConsumption', 'electricityCost', 'waterConsumption', 'avgPowerFactor', 'carbonEmission',
            'trendData', 'hourlyData', 'hourlyToday', 'energyBreakdown', 'topConsumers',
            'byBuilding', 'byFloor', 'byCategory',
            'monthlyCompare', 'lastMonthKwh', 'vsLastMonth', 'forecastNextMonth', 'projectedThisMonth',
            'electricityRate', 'waterRate', 'solarFeedinRate', 'currencySymbol'
        ));
    }

    /**
     * Top consumers grouped by equipment category — uses real per-equipment meters when available.
     */
    private function buildTopByCategory($buildingId): array
    {
        $catColors = [
            'HVAC' => '#3b82f6', 'Chillers' => '#0ea5e9', 'Lighting' => '#f59e0b',
            'Pumps' => '#06b6d4', 'Elevators' => '#a855f7', 'Power' => '#eab308',
            'Sensors' => '#10b981', 'Fire Alarm' => '#ef4444',
            'Security' => '#ec4899', 'Access Control' => '#84cc16',
        ];

        $rows = EnergyMeter::query()
            ->whereNotNull('equipment_id')
            ->where('type', 'electricity')
            ->when($buildingId, fn($q) => $q->where('building_id', $buildingId))
            ->with('equipment.category')
            ->get()
            ->groupBy(fn($m) => $m->equipment?->category?->name ?? 'Other')
            ->map(function ($meters, $catName) {
                $meterIds = $meters->pluck('id');
                $kwh = (float) EnergyLog::whereIn('meter_id', $meterIds)
                    ->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])
                    ->sum('value');
                return ['name' => $catName, 'kwh' => round($kwh, 1), 'count' => $meters->count()];
            })
            ->values()
            ->all();

        // If no per-equipment data yet, fall back to estimating by equipment count
        if (empty($rows) || array_sum(array_column($rows, 'kwh')) === 0.0) {
            $rows = $this->fallbackCategoryEstimate($buildingId);
        }

        $total = array_sum(array_column($rows, 'kwh')) ?: 1;
        $rows = array_map(function ($r) use ($catColors, $total) {
            return array_merge($r, [
                'color' => $catColors[$r['name']] ?? '#6b7280',
                'pct'   => round($r['kwh'] / $total * 100, 1),
            ]);
        }, $rows);

        usort($rows, fn($a, $b) => $b['kwh'] <=> $a['kwh']);
        return $rows;
    }

    private function fallbackCategoryEstimate($buildingId): array
    {
        $weights = ['HVAC'=>0.42,'Chillers'=>0.18,'Lighting'=>0.15,'Pumps'=>0.08,
            'Elevators'=>0.06,'Power'=>0.04,'Sensors'=>0.02,'Fire Alarm'=>0.02,
            'Security'=>0.02,'Access Control'=>0.01];
        $monthKwh = (float) EnergyLog::whereHas('meter', fn($q) =>
            $q->where('type','electricity')->when($buildingId, fn($qq) => $qq->where('building_id',$buildingId))
        )->whereBetween('logged_at', [Carbon::now()->startOfMonth(), Carbon::now()])->sum('value');

        return EquipmentCategory::withCount(['equipment' => function ($q) use ($buildingId) {
                $q->when($buildingId, fn($qq) => $qq->where('building_id', $buildingId));
            }])->get()->filter(fn($c) => $c->equipment_count > 0)
            ->map(fn($c) => ['name' => $c->name, 'kwh' => round($monthKwh * ($weights[$c->name] ?? 0.05) * max($c->equipment_count, 1) / 3, 1), 'count' => $c->equipment_count])
            ->values()->all();
    }

    private function getRealSolarSeries($solarMeterIds): array
    {
        $today = Carbon::today();
        $hourly = [];
        for ($h = 0; $h < 24; $h++) {
            $hourly[] = round((float) EnergyLog::whereIn('meter_id', $solarMeterIds)
                ->whereDate('logged_at', $today)
                ->whereRaw('HOUR(logged_at) = ?', [$h])
                ->sum('value'), 1);
        }

        $daily = [];
        for ($d = 29; $d >= 0; $d--) {
            $date = Carbon::today()->subDays($d);
            $daily[] = round((float) EnergyLog::whereIn('meter_id', $solarMeterIds)
                ->whereDate('logged_at', $date)->sum('value'), 1);
        }
        return [$hourly, $daily];
    }

    private function buildYearlyCompare($meterIds, float $thisMonthSoFar, float $projectedThisMonth, float $dailyAvg): array
    {
        $now = Carbon::now();
        $year = $now->year;
        $currentMonth = $now->month;
        $labels = $actual = $forecast = [];

        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $labels[] = $start->format('M');

            if ($m < $currentMonth) {
                $actual[] = round((float) EnergyLog::whereIn('meter_id', $meterIds)
                    ->whereBetween('logged_at', [$start, $end])->sum('value'), 1);
                $forecast[] = 0;
            } elseif ($m === $currentMonth) {
                $actual[] = round($thisMonthSoFar, 1);
                $forecast[] = round(max($projectedThisMonth - $thisMonthSoFar, 0), 1);
            } else {
                $actual[] = 0;
                $forecast[] = round($dailyAvg * $end->day, 1);
            }
        }

        return ['labels'=>$labels, 'actual'=>$actual, 'forecast'=>$forecast, 'year'=>$year, 'currentMonthIndex'=>$currentMonth-1];
    }

    private function getRealTrendData($meterIds, $startDate, $endDate): array
    {
        $days = $current = $previous = [];
        $period = $startDate->copy();
        while ($period <= $endDate) {
            $days[] = $period->format('d M');
            $current[] = round((float) EnergyLog::whereIn('meter_id', $meterIds)
                ->whereDate('logged_at', $period)->sum('value'), 0);
            $previous[] = round((float) EnergyLog::whereIn('meter_id', $meterIds)
                ->whereDate('logged_at', $period->copy()->subDay())->sum('value'), 0);
            $period->addDay();
        }
        return ['days' => $days, 'current' => $current, 'previous' => $previous];
    }

    private function getRealHourlyToday($meterIds): array
    {
        $hours = $values = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = sprintf('%02d:00', $h);
            $values[] = round((float) EnergyLog::whereIn('meter_id', $meterIds)
                ->whereDate('logged_at', Carbon::today())
                ->whereRaw('HOUR(logged_at) = ?', [$h])
                ->sum('value'), 2);
        }
        return ['hours' => $hours, 'values' => $values];
    }
}
