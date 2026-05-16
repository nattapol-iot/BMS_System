<?php
namespace App\Http\Controllers;

use App\Models\Floor;
use App\Models\Building;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FloorController extends Controller
{
    public function updatePositions(Request $request, Floor $floor)
    {
        $data = $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|integer|exists:equipment,id',
            'positions.*.x' => 'required|numeric|min:0|max:800',
            'positions.*.y' => 'required|numeric|min:0|max:500',
        ]);
        DB::transaction(function () use ($data, $floor) {
            foreach ($data['positions'] as $pos) {
                Equipment::where('id', $pos['id'])
                    ->where('floor_id', $floor->id)
                    ->update(['x_position' => $pos['x'], 'y_position' => $pos['y']]);
            }
        });
        return response()->json(['success' => true, 'updated' => count($data['positions'])]);
    }

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
