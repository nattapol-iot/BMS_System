<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EnergyMeter;
use App\Models\EnergyLog;
use App\Models\Building;
use Carbon\Carbon;

class EnergySeeder extends Seeder
{
    public function run(): void
    {
        $buildings = Building::all();

        foreach ($buildings as $building) {
            // Electricity meter
            $elecMeter = EnergyMeter::firstOrCreate(
                ['code' => "EM-{$building->code}-01"],
                ['name'=>"{$building->name} Electricity","building_id"=>$building->id,'type'=>'electricity','unit'=>'kWh']
            );
            // Water meter
            $waterMeter = EnergyMeter::firstOrCreate(
                ['code' => "WM-{$building->code}-01"],
                ['name'=>"{$building->name} Water","building_id"=>$building->id,'type'=>'water','unit'=>'m3']
            );

            // Generate 30 days of logs
            for ($d = 30; $d >= 0; $d--) {
                $date = Carbon::today()->subDays($d);
                // Generate hourly data for each day
                for ($h = 0; $h < 24; $h++) {
                    $loggedAt = $date->copy()->setHour($h);
                    $baseElec = ($h >= 8 && $h <= 18) ? rand(200, 400) : rand(50, 150);
                    EnergyLog::create([
                        'meter_id' => $elecMeter->id,
                        'value' => $baseElec,
                        'peak_demand' => $baseElec * 1.3,
                        'power_factor' => round(rand(85, 98) / 100, 2),
                        'cost' => round($baseElec * 4.2, 2),
                        'logged_at' => $loggedAt,
                    ]);
                    $waterVal = ($h >= 7 && $h <= 19) ? rand(10, 30) : rand(2, 8);
                    EnergyLog::create([
                        'meter_id' => $waterMeter->id,
                        'value' => $waterVal,
                        'logged_at' => $loggedAt,
                    ]);
                }
            }
        }
    }
}
