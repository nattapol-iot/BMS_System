<?php
namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Building;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('generator')->orderByDesc('created_at')->paginate(15);
        $buildings = Building::where('status','active')->get();
        return view('reports.index', compact('reports','buildings'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'type' => 'required|in:status,energy,alarm,maintenance',
            'format' => 'required|in:pdf,excel',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $report = Report::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'format' => $data['format'],
            'parameters' => ['start_date'=>$data['start_date']??null,'end_date'=>$data['end_date']??null],
            'generated_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('reports.index')->with('success','Report queued for generation.');
    }
}
