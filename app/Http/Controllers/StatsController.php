<?php
namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        
        $totalTrades = Trade::where('user_id', $userId)->count();
        $closedTrades = Trade::where('user_id', $userId)->where('status', 'CLOSED');
        $openTrades = Trade::where('user_id', $userId)->where('status', 'OPEN');
        
        $winningTrades = (clone $closedTrades)->where('pnl', '>', 0)->count();
        $losingTrades = (clone $closedTrades)->where('pnl', '<', 0)->count();
        $totalClosed = (clone $closedTrades)->count();
        $winRate = $totalClosed > 0 ? round($winningTrades / $totalClosed * 100, 1) : 0;
        
        $totalPnl = Trade::where('user_id', $userId)->sum('pnl') ?? 0;
        $bestTrade = (clone $closedTrades)->orderBy('pnl', 'desc')->first();
        $worstTrade = (clone $closedTrades)->orderBy('pnl', 'asc')->first();
        
        // Profit factor
        $grossProfit = abs((clone $closedTrades)->where('pnl', '>', 0)->sum('pnl') ?? 0);
        $grossLoss = abs((clone $closedTrades)->where('pnl', '<', 0)->sum('pnl') ?? 0);
        $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : 0;
        
        // P&L by instrument
        $byInstrument = Trade::where('user_id', $userId)
            ->select('instrument', DB::raw('COUNT(*) as trades'), DB::raw('SUM(pnl) as total_pnl'))
            ->groupBy('instrument')->get();
        
        // P&L by strategy
        $byStrategy = Trade::where('user_id', $userId)
            ->select('strategy_name', DB::raw('COUNT(*) as trades'), DB::raw('SUM(pnl) as total_pnl'))
            ->groupBy('strategy_name')->get();
        
        // Monthly P&L
        $monthlyPnl = Trade::where('user_id', $userId)
            ->select(DB::raw("strftime('%Y-%m', entry_time) as month"), DB::raw('SUM(pnl) as pnl'))
            ->groupBy('month')->orderBy('month')->get();
        
        return Inertia::render('TradingStats', [
            'stats' => [
                'totalTrades' => $totalTrades,
                'winningTrades' => $winningTrades,
                'losingTrades' => $losingTrades,
                'totalClosed' => $totalClosed,
                'winRate' => $winRate,
                'totalPnl' => round($totalPnl, 2),
                'profitFactor' => $profitFactor,
                'bestTrade' => $bestTrade,
                'worstTrade' => $worstTrade,
                'avgPnl' => $totalClosed > 0 ? round($totalPnl / $totalClosed, 2) : 0,
            ],
            'byInstrument' => $byInstrument,
            'byStrategy' => $byStrategy,
            'monthlyPnl' => $monthlyPnl,
            'openTrades' => (clone $openTrades)->count(),
        ]);
    }
}
