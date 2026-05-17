<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Equipment;
use App\Models\EnergyMeter;
use App\Models\EnergyLog;
use Carbon\Carbon;

/**
 * Seeds solar PV meters per building and per-equipment electricity meters
 * for the biggest consumers (HVAC, Chillers, Pumps, Elevators).
 *
 * Idempotent — uses firstOrCreate so it can be re-run safely.
 */
class AdvancedMeterSeeder extends Seeder
{
    public function run(): void
    {
        $buildings = Building::all();
        if ($buildings->isEmpty()) {
            $this->command->warn('No buildings — run BuildingSeeder first.');
            return;
        }

        // --- 1. Solar PV meter per building ---
        foreach ($buildings as $b) {
            $meter = EnergyMeter::firstOrCreate(
                ['code' => 'SOL-' . strtoupper(str_replace([' ', '_'], '', $b->name))],
                [
                    'name'        => $b->name . ' Solar PV',
                    'building_id' => $b->id,
                    'type'        => 'solar',
                    'unit'        => 'kWh',
                    'status'      => 'active',
                ]
            );
            $this->seedSolarLogs($meter);
        }

        // --- 2. Per-equipment electricity meters for high-consumption gear ---
        $heavyCategoryNames = ['HVAC', 'Chillers', 'Pumps', 'Elevators'];
        $heavyEquipment = Equipment::whereHas('category', fn($q) => $q->whereIn('name', $heavyCategoryNames))->get();

        foreach ($heavyEquipment as $eq) {
            $meter = EnergyMeter::firstOrCreate(
                ['code' => 'M-' . $eq->code],
                [
                    'name'         => $eq->name . ' (sub-meter)',
                    'building_id'  => $eq->building_id,
                    'floor_id'     => $eq->floor_id,
                    'equipment_id' => $eq->id,
                    'type'         => 'electricity',
                    'unit'         => 'kWh',
                    'status'       => 'active',
                ]
            );
            $this->seedEquipmentLogs($meter, $eq);
        }

        $this->command->info(sprintf(
            'AdvancedMeterSeeder: %d solar meters + %d per-equipment meters seeded.',
            $buildings->count(), $heavyEquipment->count()
        ));
    }

    private function seedSolarLogs(EnergyMeter $meter): void
    {
        if (EnergyLog::where('meter_id', $meter->id)->exists()) return;

        $start = Carbon::now()->subDays(30)->startOfDay();
        $end = Carbon::now();
        $cursor = $start->copy();

        $rows = [];
        while ($cursor <= $end) {
            $hour = $cursor->hour;
            // Bell curve from 6am to 6pm, peak at 13:00, max ~80 kWh/hour
            if ($hour < 6 || $hour > 18) {
                $kwh = 0;
            } else {
                $sun = sin((($hour - 6) / 12) * M_PI);
                $kwh = round(75 * $sun + mt_rand(-5, 5), 2);
                if ($kwh < 0) $kwh = 0;
            }
            $rows[] = [
                'meter_id' => $meter->id,
                'value' => $kwh,
                'logged_at' => $cursor->copy(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $cursor->addHour();
            if (count($rows) >= 500) {
                EnergyLog::insert($rows);
                $rows = [];
            }
        }
        if (!empty($rows)) EnergyLog::insert($rows);
    }

    private function seedEquipmentLogs(EnergyMeter $meter, Equipment $eq): void
    {
        if (EnergyLog::where('meter_id', $meter->id)->exists()) return;

        // Category-specific consumption profiles (kWh/hour average)
        $profile = match ($eq->category?->name) {
            'HVAC'       => ['base' => 25, 'peak' => 55],     // active 6:00-22:00
            'Chillers'   => ['base' => 80, 'peak' => 180],
            'Pumps'      => ['base' => 6, 'peak' => 14],
            'Elevators'  => ['base' => 3, 'peak' => 8],
            default      => ['base' => 2, 'peak' => 5],
        };

        $start = Carbon::now()->subDays(30)->startOfDay();
        $end = Carbon::now();
        $cursor = $start->copy();
        $rows = [];

        while ($cursor <= $end) {
            $hour = $cursor->hour;
            // Day cycle (active hours)
            $active = $hour >= 6 && $hour <= 22;
            if ($active) {
                // Peak load 10-16
                $peakWeight = ($hour >= 10 && $hour <= 16) ? 1.0 : 0.6;
                $kwh = round($profile['base'] + ($profile['peak'] - $profile['base']) * $peakWeight * (mt_rand(85, 115) / 100), 2);
            } else {
                $kwh = round($profile['base'] * 0.3 * (mt_rand(70, 130) / 100), 2);
            }

            $rows[] = [
                'meter_id' => $meter->id,
                'value' => $kwh,
                'logged_at' => $cursor->copy(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $cursor->addHour();
            if (count($rows) >= 500) {
                EnergyLog::insert($rows);
                $rows = [];
            }
        }
        if (!empty($rows)) EnergyLog::insert($rows);
    }
}
