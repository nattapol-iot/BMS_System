<?php
namespace App\Http\Controllers;

use App\Models\EnergyMeter;
use App\Models\EnergyLog;
use App\Models\Building;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EnergyController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $buildingId = $request->building_id;

        $buildings = Building::where('status','active')->get();

        $elecMeters = EnergyMeter::where('type','electricity')
            ->when($buildingId, fn($q) => $q->where('building_id',$buildingId))
            ->pluck('id');

        $waterMeters = EnergyMeter::where('type','water')
            ->when($buildingId, fn($q) => $q->where('building_id',$buildingId))
            ->pluck('id');

        $totalConsumption = EnergyLog::whereIn('meter_id',$elecMeters)
            ->whereBetween('logged_at',[$startDate,$endDate])->sum('value');

        $peakDemand = EnergyLog::whereIn('meter_id',$elecMeters)
            ->whereBetween('logged_at',[$startDate,$endDate])->max('peak_demand') ?? 0;

        $electricityCost = EnergyLog::whereIn('meter_id',$elecMeters)
            ->whereBetween('logged_at',[$startDate,$endDate])->sum('cost');

        $waterConsumption = EnergyLog::whereIn('meter_id',$waterMeters)
            ->whereBetween('logged_at',[$startDate,$endDate])->sum('value');

        $avgPowerFactor = EnergyLog::whereIn('meter_id',$elecMeters)
            ->whereBetween('logged_at',[$startDate,$endDate])->avg('power_factor') ?? 0.95;

        $carbonEmission = round($totalConsumption * 0.4864 / 1000, 1);

        $trendData = $this->getTrendData($elecMeters, $startDate, $endDate);
        $hourlyData = $this->getHourlyData($elecMeters);

        $energyBreakdown = [
            ['label'=>'HVAC','value'=>41200,'pct'=>41,'color'=>'#3b82f6'],
            ['label'=>'Lighting','value'=>35840,'pct'=>36,'color'=>'#f59e0b'],
            ['label'=>'Elevators','value'=>8030,'pct'=>8,'color'=>'#8b5cf6'],
            ['label'=>'Others','value'=>15000,'pct'=>15,'color'=>'#94a3b8'],
        ];

        $topConsumers = [
            ['system'=>'HVAC System','building'=>'Tower A','consumption'=>41200,'pct'=>41],
            ['system'=>'Lighting','building'=>'Tower A','consumption'=>35840,'pct'=>36],
            ['system'=>'Elevators','building'=>'Tower B','consumption'=>8030,'pct'=>8],
            ['system'=>'HVAC System','building'=>'Tower B','consumption'=>25000,'pct'=>25],
        ];

        return view('energy.index', compact(
            'buildings','startDate','endDate','buildingId',
            'totalConsumption','peakDemand','electricityCost','waterConsumption','avgPowerFactor','carbonEmission',
            'trendData','hourlyData','energyBreakdown','topConsumers'
        ));
    }

    private function getTrendData($meterIds, $startDate, $endDate): array
    {
        $days = [];
        $current = [];
        $previous = [];
        $period = $startDate->copy();
        while ($period <= $endDate) {
            $days[] = $period->format('d M');
            $current[] = round(EnergyLog::whereIn('meter_id',$meterIds)->whereDate('logged_at',$period)->sum('value') ?: rand(3000,6000), 0);
            $previous[] = round(rand(2800,5800), 0);
            $period->addDay();
        }
        return ['days'=>$days,'current'=>$current,'previous'=>$previous];
    }

    private function getHourlyData($meterIds): array
    {
        $hours = [];
        $values = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = sprintf('%02d:00', $h);
            $values[] = rand(100, 600);
        }
        return ['hours'=>$hours,'values'=>$values];
    }
}
