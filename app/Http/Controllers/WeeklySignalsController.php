<?php
namespace App\Http\Controllers;

use App\Models\WeeklySignal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeeklySignalsController extends Controller
{
    public function index()
    {
        $signals = WeeklySignal::where('user_id', auth()->id())
            ->orderBy('week_label', 'desc')
            ->orderBy('priority')
            ->get();
        
        return Inertia::render('WeeklySignals', [
            'signals' => $signals,
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'week_label' => 'required|string',
            'instrument' => 'required|string',
            'direction' => 'required|in:LONG,SHORT',
            'entry_price' => 'required|numeric',
            'stop_loss' => 'required|numeric',
            'take_profit' => 'required|numeric',
            'priority' => 'required|integer|min:1|max:3',
            'catalyst' => 'required|string',
            'analysis' => 'nullable|string',
        ]);
        
        $validated['user_id'] = auth()->id();
        WeeklySignal::create($validated);
        
        return back()->with('success', 'Signal ajouté');
    }
}
