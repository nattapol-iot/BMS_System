<?php
namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\Building;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $results = Equipment::with(['category','building','floor'])
            ->when($q !== '', fn($qb) => $qb->where(fn($w) => $w
                ->where('name','like',"%{$q}%")
                ->orWhere('code','like',"%{$q}%")))
            ->limit(20)
            ->get(['id','name','code','category_id','building_id','floor_id']);
        return response()->json($results);
    }

    public function index(Request $request)
    {
        $query = Equipment::with(['category','building','floor']);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name','like','%'.$request->search.'%')
                  ->orWhere('code','like','%'.$request->search.'%');
            });
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('building_id')) {
            $query->where('building_id', $request->building_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $equipment = $query->paginate(15)->withQueryString();
        $categories = EquipmentCategory::all();
        $buildings = Building::where('status','active')->get();

        $totalEquipment = Equipment::count();
        $activeDevices = Equipment::where('status','active')->count();
        $maintenanceDue = Equipment::where('status','maintenance')->count();
        $offlineDevices = Equipment::where('status','offline')->count();

        $selectedEquipment = null;
        if ($request->filled('detail')) {
            $selectedEquipment = Equipment::with(['category','building','floor','room','alarms'=>fn($q)=>$q->limit(5)->latest()])->find($request->detail);
        }

        return view('equipment.index', compact(
            'equipment','categories','buildings',
            'totalEquipment','activeDevices','maintenanceDue','offlineDevices',
            'selectedEquipment'
        ));
    }

    public function show(Equipment $equipment)
    {
        $equipment->load(['category','building','floor','room','alarms'=>fn($q)=>$q->limit(5)->latest(),'statusLogs'=>fn($q)=>$q->latest()->limit(10)]);
        return response()->json($equipment);
    }

    public function create()
    {
        $categories = EquipmentCategory::all();
        $buildings = Building::where('status','active')->with('floors')->get();
        return view('equipment.create', compact('categories','buildings'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:equipment|max:30',
            'name' => 'required|max:255',
            'manufacturer' => 'nullable|max:100',
            'model_number' => 'nullable|max:100',
            'building_id' => 'required|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'category_id' => 'required|exists:equipment_categories,id',
            'status' => 'required|in:active,inactive,offline,maintenance',
            'health_score' => 'nullable|integer|between:0,100',
            'notes' => 'nullable',
        ]);
        Equipment::create($data);
        return redirect()->route('equipment.index')->with('success', 'Equipment created.');
    }

    public function edit(Equipment $equipment)
    {
        $categories = EquipmentCategory::all();
        $buildings = Building::where('status','active')->with('floors')->get();
        return view('equipment.edit', compact('equipment','categories','buildings'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'manufacturer' => 'nullable|max:100',
            'model_number' => 'nullable|max:100',
            'building_id' => 'required|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'category_id' => 'required|exists:equipment_categories,id',
            'status' => 'required|in:active,inactive,offline,maintenance',
            'health_score' => 'nullable|integer|between:0,100',
            'notes' => 'nullable',
        ]);
        $equipment->update($data);
        return redirect()->route('equipment.index')->with('success', 'Equipment updated.');
    }

    public function destroy(Equipment $equipment)
    {
        $equipment->delete();
        return redirect()->route('equipment.index')->with('success', 'Equipment deleted.');
    }
}
