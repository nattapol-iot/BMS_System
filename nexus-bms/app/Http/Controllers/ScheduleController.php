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

        $schedules = Schedule::with(['building','equipment'])->orderByDesc('updated_at')->get();
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

    public function deviceSettings()
    {
        $buildings = Building::where('status','active')->get();
        $categories = EquipmentCategory::all();
        $schedules = Schedule::with(['building','floor','equipment'])->orderByDesc('created_at')->paginate(10);
        return view('schedules.device-settings', compact('buildings','categories','schedules'));
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

        $schedule = Schedule::create($data);
        if ($request->filled('equipment_ids')) {
            $schedule->equipment()->sync($request->equipment_ids);
        }

        return redirect()->route('schedules.device-settings')->with('success','Schedule saved successfully.');
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
