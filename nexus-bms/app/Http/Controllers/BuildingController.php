<?php
namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use Illuminate\Http\Request;

class BuildingController extends Controller
{
    public function index(Request $request)
    {
        $query = Building::with(['floors', 'alarms', 'equipment']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $buildings = $query->paginate(9)->withQueryString();
        $totalBuildings = Building::count();
        $activeBuildings = Building::where('status', 'active')->count();

        return view('buildings.index', compact('buildings', 'totalBuildings', 'activeBuildings'));
    }

    public function show(Building $building)
    {
        $building->load(['floors', 'equipment.category', 'alarms' => fn($q) => $q->whereIn('status', ['active','acknowledged'])->latest()->limit(5)]);
        return response()->json($building);
    }

    public function create()
    {
        return view('buildings.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|unique:buildings|max:20',
            'name' => 'required|max:255',
            'name_th' => 'nullable|max:255',
            'address' => 'nullable',
            'city' => 'nullable|max:100',
            'floors_count' => 'required|integer|min:1',
            'total_area' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);
        $data['created_by'] = auth()->id();
        Building::create($data);
        return redirect()->route('buildings.index')->with('success', 'Building created successfully.');
    }

    public function edit(Building $building)
    {
        return view('buildings.edit', compact('building'));
    }

    public function update(Request $request, Building $building)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'name_th' => 'nullable|max:255',
            'address' => 'nullable',
            'city' => 'nullable|max:100',
            'floors_count' => 'required|integer|min:1',
            'total_area' => 'nullable|numeric',
            'status' => 'required|in:active,inactive',
        ]);
        $building->update($data);
        return redirect()->route('buildings.index')->with('success', 'Building updated.');
    }

    public function destroy(Building $building)
    {
        $building->delete();
        return redirect()->route('buildings.index')->with('success', 'Building deleted.');
    }
}
