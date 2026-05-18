<?php
namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradeComment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $query = Trade::where('user_id', $userId);
        
        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('instrument')) {
            $query->where('instrument', $request->instrument);
        }
        if ($request->filled('side')) {
            $query->where('side', $request->side);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('strategy_name', 'like', "%{$s}%")
                  ->orWhere('catalyst', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%");
            });
        }
        
        $trades = $query->orderBy('entry_time', 'desc')->paginate(20)->withQueryString();
        
        // Stats summary
        $closed = Trade::where('user_id', $userId)->where('status', 'CLOSED');
        $totalPnl = $closed->sum('pnl');
        $winCount = (clone $closed)->where('pnl', '>', 0)->count();
        $totalCount = (clone $closed)->count();
        
        // Available filters
        $instruments = Trade::where('user_id', $userId)->select('instrument')->distinct()->pluck('instrument');
        
        return Inertia::render('TradeHistory', [
            'trades' => $trades,
            'stats' => [
                'totalPnl' => round($totalPnl, 2),
                'winRate' => $totalCount > 0 ? round($winCount / $totalCount * 100, 1) : 0,
                'totalClosed' => $totalCount,
            ],
            'filters' => [
                'instruments' => $instruments,
                'current' => $request->only(['status', 'instrument', 'side', 'search']),
            ]
        ]);
    }
    
    public function update(Request $request, Trade $trade)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'pnl' => 'nullable|numeric',
            'exit_price' => 'nullable|numeric',
            'exit_time' => 'nullable|date',
            'status' => 'nullable|in:PENDING,OPEN,CLOSED,CANCELLED',
        ]);
        
        $trade->update($validated);
        return back()->with('success', 'Trade mis à jour');
    }
    
    public function addComment(Request $request, Trade $trade)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'nullable|in:note,lesson,tag',
        ]);
        
        $trade->comments()->create([
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'note',
        ]);
        
        return back()->with('success', 'Commentaire ajouté');
    }
}
