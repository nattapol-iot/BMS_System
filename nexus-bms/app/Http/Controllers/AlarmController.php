<?php
namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\AlarmEvent;
use App\Models\Building;
use Illuminate\Http\Request;

class AlarmController extends Controller
{
    public function index(Request $request)
    {
        $query = Alarm::with(['equipment','building','floor','assignee']);

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }
        if ($request->filled('search')) {
            $query->where('description','like','%'.$request->search.'%')
                  ->orWhere('code','like','%'.$request->search.'%');
        }

        $alarms = $query->orderByRaw("FIELD(severity,'critical','warning','info')")
            ->orderByDesc('triggered_at')
            ->paginate(15)->withQueryString();

        $totalAlerts = Alarm::count();
        $criticalCount = Alarm::where('severity','critical')->where('status','active')->count();
        $warningCount = Alarm::where('severity','warning')->whereIn('status',['active','acknowledged'])->count();
        $acknowledgedCount = Alarm::where('status','acknowledged')->count();
        $resolvedToday = Alarm::where('status','resolved')->whereDate('resolved_at',today())->count();

        $buildings = Building::where('status','active')->get();
        $selectedAlarm = null;
        if ($request->filled('detail')) {
            $selectedAlarm = Alarm::with(['equipment','building','floor','assignee','events.performer'])->find($request->detail);
        }

        $recentEvents = AlarmEvent::with(['alarm','performer'])->latest()->limit(10)->get();

        return view('alarms.index', compact(
            'alarms','totalAlerts','criticalCount','warningCount',
            'acknowledgedCount','resolvedToday','buildings','selectedAlarm','recentEvents'
        ));
    }

    public function show(Alarm $alarm)
    {
        $alarm->load(['equipment','building','floor','assignee','events.performer']);
        return response()->json($alarm);
    }

    public function acknowledge(Request $request, Alarm $alarm)
    {
        $alarm->update(['status'=>'acknowledged','acknowledged_at'=>now()]);
        AlarmEvent::create(['alarm_id'=>$alarm->id,'event_type'=>'acknowledged','performed_by'=>auth()->id(),'note'=>$request->note]);
        return response()->json(['success'=>true,'message'=>'Alarm acknowledged']);
    }

    public function resolve(Request $request, Alarm $alarm)
    {
        $alarm->update(['status'=>'resolved','resolved_at'=>now()]);
        AlarmEvent::create(['alarm_id'=>$alarm->id,'event_type'=>'resolved','performed_by'=>auth()->id(),'note'=>$request->note]);
        return response()->json(['success'=>true,'message'=>'Alarm resolved']);
    }

    public function silence(Request $request, Alarm $alarm)
    {
        $minutes = $request->input('minutes', 30);
        $until = now()->addMinutes($minutes);
        $alarm->update(['status'=>'silenced','silenced_until'=>$until]);
        AlarmEvent::create(['alarm_id'=>$alarm->id,'event_type'=>'silenced','performed_by'=>auth()->id(),'note'=>"Silenced for {$minutes} minutes"]);
        return response()->json(['success'=>true,'message'=>'Alarm silenced']);
    }

    public function assign(Request $request, Alarm $alarm)
    {
        $request->validate(['user_id'=>'required|exists:users,id']);
        $alarm->update(['assigned_to'=>$request->user_id]);
        AlarmEvent::create(['alarm_id'=>$alarm->id,'event_type'=>'acknowledged','performed_by'=>auth()->id(),'note'=>"Assigned to user #{$request->user_id}"]);
        return response()->json(['success'=>true]);
    }
}
