<?php
namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Building;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index()
    {
        $totalSchedules = Schedule::count();
        $activeSchedules = Schedule::where('status','active')->count();
        $runningNow = Schedule::where('status','active')
            ->where('turn_on_time','<=',now()->format('H:i:s'))
            ->where(fn($q)=>$q->whereNull('turn_off_time')->orWhere('turn_off_time','>=',now()->format('H:i:s')))
            ->count();

        $schedules = Schedule::with(['building','equipment'])->orderByDesc('updated_at')->paginate(15);
        $todaySchedules = Schedule::with(['building','equipment'])
            ->where('status','active')
            ->whereJsonContains('repeat_days', strtolower(Carbon::today()->format('D')))
            ->get();

        $categoryStats = $this->getCategoryStats();

        return view('schedules.index', compact(
            'totalSchedules','activeSchedules','runningNow','schedules','todaySchedules','categoryStats'
        ));
    }

    public function calendar()
    {
        $month = request('month', now()->month);
        $year = request('year', now()->year);
        $schedules = Schedule::with(['building','equipment'])->where('status','active')->get();
        $buildings = Building::where('status','active')->get();
        return view('schedules.calendar', compact('schedules','month','year','buildings'));
    }

    public function saveDeviceSettings(Request $request, Schedule $schedule)
    {
        $payload = $request->validate([
            'devices' => 'sometimes|array',
            'devices.*.on_time' => 'nullable|date_format:H:i',
            'devices.*.off_time' => 'nullable|date_format:H:i',
            'devices.*.days' => 'sometimes|array',
            'devices.*.days.*' => 'integer|between:0,6',
            'devices.*._remove' => 'sometimes|in:0,1',
        ]);

        foreach ($payload['devices'] ?? [] as $devId => $fields) {
            $sd = \App\Models\ScheduleDevice::where('id', $devId)
                ->where('schedule_id', $schedule->id)
                ->first();
            if (!$sd) continue;

            if (!empty($fields['_remove']) && $fields['_remove'] === '1') {
                $sd->delete();
                continue;
            }

            $sd->update([
                'on_time' => $fields['on_time'] ?? null,
                'off_time' => $fields['off_time'] ?? null,
                'days' => $fields['days'] ?? null,
            ]);
        }

        // Optional: attach new equipment from the modal
        if ($request->filled('add_equipment_id')) {
            \App\Models\ScheduleDevice::firstOrCreate([
                'schedule_id' => $schedule->id,
                'equipment_id' => (int) $request->add_equipment_id,
            ]);
        }

        return redirect()->route('schedules.device-settings', ['schedule_id' => $schedule->id])
            ->with('success', 'Device settings saved.');
    }

    public function addDevice(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
        ]);
        \App\Models\ScheduleDevice::firstOrCreate([
            'schedule_id' => $schedule->id,
            'equipment_id' => $data['equipment_id'],
        ]);
        return redirect()->route('schedules.device-settings', ['schedule_id' => $schedule->id])
            ->with('success', 'Device added.');
    }

    public function deviceSettings(Request $request)
    {
        $buildings = Building::where('status','active')->get();
        $categories = EquipmentCategory::all();
        $schedules = Schedule::with(['building','equipment'])->orderByDesc('updated_at')->get();

        $selectedSchedule = $request->filled('schedule_id')
            ? Schedule::with(['equipment.category','equipment.building','equipment.floor'])->find($request->schedule_id)
            : null;

        $devices = $selectedSchedule
            ? $selectedSchedule->equipment()->with(['category','building','floor'])->get()
            : collect();

        return view('schedules.device-settings', compact('buildings','categories','schedules','selectedSchedule','devices'));
    }

    public function create()
    {
        $buildings = Building::where('status','active')->get();
        $equipment = Equipment::with(['category','building'])->orderBy('name')->get();
        return view('schedules.create', compact('buildings','equipment'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'category' => 'nullable|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'turn_on_time' => 'required',
            'turn_off_time' => 'nullable',
            'recurrence' => 'required|in:daily,weekly,monthly,once',
            'status' => 'required|in:active,inactive,disabled',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipment,id',
        ]);

        $data['created_by'] = auth()->id();
        $data['repeat_days'] = $request->input('repeat_days', []);
        $equipmentIds = $data['equipment_ids'] ?? [];
        unset($data['equipment_ids']);

        $schedule = Schedule::create($data);
        if (!empty($equipmentIds)) {
            $schedule->equipment()->sync($equipmentIds);
        }

        return redirect()->route('schedules.index')->with('success','Schedule created successfully.');
    }

    public function edit(Schedule $schedule)
    {
        $buildings = Building::where('status','active')->get();
        $equipment = Equipment::with(['category','building'])->orderBy('name')->get();
        $schedule->load('equipment');
        return view('schedules.edit', compact('schedule','buildings','equipment'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'category' => 'nullable|max:100',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'turn_on_time' => 'required',
            'turn_off_time' => 'nullable',
            'recurrence' => 'required|in:daily,weekly,monthly,once',
            'status' => 'required|in:active,inactive,disabled',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipment,id',
        ]);
        $data['repeat_days'] = $request->input('repeat_days', []);
        $equipmentIds = $data['equipment_ids'] ?? [];
        unset($data['equipment_ids']);

        $schedule->update($data);
        $schedule->equipment()->sync($equipmentIds);

        return redirect()->route('schedules.index')->with('success','Schedule updated successfully.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->equipment()->detach();
        $schedule->delete();
        return redirect()->route('schedules.index')->with('success','Schedule deleted.');
    }

    public function toggle(Schedule $schedule)
    {
        $schedule->update(['status' => $schedule->status === 'active' ? 'inactive' : 'active']);
        return response()->json(['success'=>true,'status'=>$schedule->status]);
    }

    private function getCategoryStats(): array
    {
        return [
            ['category'=>'HVAC','count'=>Schedule::where('category','HVAC')->count(),'color'=>'#3b82f6'],
            ['category'=>'Lighting','count'=>Schedule::where('category','Lighting')->count(),'color'=>'#f59e0b'],
            ['category'=>'Access Control','count'=>Schedule::where('category','Access Control')->count(),'color'=>'#22c55e'],
            ['category'=>'Maintenance','count'=>Schedule::where('category','Maintenance')->count(),'color'=>'#ef4444'],
        ];
    }
}
