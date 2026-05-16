<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Building;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $buildings = Building::where('status', 'active')->get();
        $selectedBuilding = null;
        $selectedFloor = null;
        $floors = collect();

        if ($request->filled('building_id')) {
            $selectedBuilding = Building::find($request->building_id);
            $floors = Floor::where('building_id', $request->building_id)
                ->with(['rooms', 'equipment.category'])
                ->orderBy('floor_number')
                ->get();
            if ($request->filled('floor_id')) {
                $selectedFloor = $floors->firstWhere('id', $request->floor_id);
            } elseif ($floors->isNotEmpty()) {
                $selectedFloor = $floors->first();
            }
        }

        return view('floors.index', compact('buildings','selectedBuilding','selectedFloor','floors'));
    }

    public function show(Floor $floor)
    {
        $floor->load(['rooms', 'equipment.category', 'building']);
        return response()->json($floor);
    }
}
