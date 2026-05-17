<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentStatusLog;
use App\Models\EnergyMeter;
use App\Models\EnergyLog;
use App\Models\Alarm;
use App\Core\AuditLog\Models\ActivityLog;
use App\Core\Notifications\NotificationService;
use Illuminate\Http\Request;

class IoTController extends Controller
{
    public function updateEquipmentStatus(Request $request, string $code, NotificationService $notify)
    {
        $data = $request->validate([
            'status' => 'sometimes|in:active,inactive,offline,maintenance',
            'health_score' => 'sometimes|integer|between:0,100',
            'runtime_hours' => 'sometimes|numeric|min:0',
        ]);

        $equipment = Equipment::where('code', $code)->first();
        if (!$equipment) {
            return response()->json(['error' => 'Equipment not found'], 404);
        }

        $previousStatus = $equipment->status;
        $previousHealth = $equipment->health_score;

        $equipment->update(array_merge($data, ['last_communication' => now()]));

        EquipmentStatusLog::create([
            'equipment_id' => $equipment->id,
            'status' => $equipment->status,
            'health_score' => $equipment->health_score,
            'message' => 'IoT status update',
        ]);

        // Auto-create alarm on critical conditions
        if ($previousStatus !== 'offline' && $equipment->status === 'offline') {
            $alarm = Alarm::create([
                'code' => 'IOT-' . strtoupper(uniqid()),
                'equipment_id' => $equipment->id,
                'building_id' => $equipment->building_id,
                'floor_id' => $equipment->floor_id,
                'severity' => 'critical',
                'status' => 'active',
                'description' => "Equipment {$equipment->code} went OFFLINE",
                'triggered_at' => now(),
            ]);
            $notify->onCriticalAlarm($alarm);
        } elseif ($previousHealth >= 50 && ($equipment->health_score ?? 100) < 50) {
            $alarm = Alarm::create([
                'code' => 'IOT-' . strtoupper(uniqid()),
                'equipment_id' => $equipment->id,
                'building_id' => $equipment->building_id,
                'floor_id' => $equipment->floor_id,
                'severity' => 'warning',
                'status' => 'active',
                'description' => "Equipment {$equipment->code} health dropped below 50% (now {$equipment->health_score}%)",
                'triggered_at' => now(),
            ]);
            $notify->onCriticalAlarm($alarm);
        }

        return response()->json([
            'success' => true,
            'equipment' => [
                'id' => $equipment->id,
                'code' => $equipment->code,
                'status' => $equipment->status,
                'health_score' => $equipment->health_score,
                'last_communication' => $equipment->last_communication?->toIso8601String(),
            ],
        ]);
    }

    public function pushMeterReading(Request $request, string $name)
    {
        $data = $request->validate([
            'value' => 'required|numeric|min:0',
            'peak_demand' => 'sometimes|numeric|min:0',
            'power_factor' => 'sometimes|numeric|between:0,1',
            'cost' => 'sometimes|numeric|min:0',
            'logged_at' => 'sometimes|date',
        ]);

        $meter = EnergyMeter::where('name', $name)->first();
        if (!$meter) {
            return response()->json(['error' => 'Meter not found'], 404);
        }

        $log = EnergyLog::create([
            'meter_id' => $meter->id,
            'value' => $data['value'],
            'peak_demand' => $data['peak_demand'] ?? null,
            'power_factor' => $data['power_factor'] ?? null,
            'cost' => $data['cost'] ?? null,
            'logged_at' => $data['logged_at'] ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'meter' => $meter->name,
            'value' => $log->value,
            'logged_at' => $log->logged_at->toIso8601String(),
        ]);
    }

    public function dashboardLive(Request $request)
    {
        $now = now();
        $today = $now->copy()->startOfDay();

        $activeAlarms = Alarm::where('status', 'active')->count();
        $criticalAlarms = Alarm::where('status', 'active')->where('severity', 'critical')->count();
        $totalEquipment = Equipment::count();
        $activeEquipment = Equipment::where('status', 'active')->count();
        $offlineEquipment = Equipment::where('status', 'offline')->count();

        $todayEnergy = EnergyLog::whereDate('logged_at', $today)
            ->whereHas('meter', fn($q) => $q->where('type', 'electricity'))
            ->sum('value');

        $systemHealth = $activeEquipment > 0
            ? (int) round(Equipment::where('status', 'active')->avg('health_score') ?? 95)
            : 95;

        return response()->json([
            'timestamp' => $now->toIso8601String(),
            'active_alarms' => $activeAlarms,
            'critical_alarms' => $criticalAlarms,
            'total_equipment' => $totalEquipment,
            'active_equipment' => $activeEquipment,
            'offline_equipment' => $offlineEquipment,
            'today_energy' => round($todayEnergy, 2),
            'system_health' => $systemHealth,
            'recent_alarms' => Alarm::with('equipment:id,code,name')
                ->where('status', 'active')
                ->latest('triggered_at')
                ->limit(5)
                ->get(['id', 'code', 'severity', 'description', 'triggered_at', 'equipment_id']),
        ]);
    }
}
