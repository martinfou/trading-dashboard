<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StrategyDeployment;
use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeploymentController extends Controller
{
    /**
     * POST /api/deployments
     * Reçoit une promotion de stratégie depuis deploy.sh ou le runner Java
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'strategy_name' => 'required|string',
            'version' => 'required|string',
            'phase' => 'required|in:backtest,paper,live,retired',
            'status' => 'required|in:pending,active,failed,rolled_back',
            'git_tag' => 'nullable|string',
            'git_commit' => 'required|string|size:40',
            'validation_checks' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Upsert: strategy_name + version + phase est unique
        $deployment = StrategyDeployment::updateOrCreate(
            [
                'strategy_name' => $data['strategy_name'],
                'version' => $data['version'],
                'phase' => $data['phase'],
            ],
            [
                'status' => $data['status'],
                'git_tag' => $data['git_tag'] ?? null,
                'git_commit' => $data['git_commit'],
                'validation_checks' => $data['validation_checks'] ?? [],
                'notes' => $data['notes'] ?? null,
                'deployed_at' => now(),
            ]
        );

        // Si promotion vers live: desactiver les autres strategies live
        if ($data['phase'] === 'live' && $data['status'] === 'active') {
            StrategyDeployment::where('strategy_name', '!=', $data['strategy_name'])
                ->where('phase', 'live')
                ->where('status', 'active')
                ->update(['status' => 'rolled_back']);
        }

        return response()->json([
            'id' => $deployment->id,
            'strategy_name' => $deployment->strategy_name,
            'version' => $deployment->version,
            'phase' => $deployment->phase,
            'status' => $deployment->status,
        ], 201);
    }

    /**
     * POST /api/deployments/{id}/trades
     * Recoit un lot de trades depuis le runner Java
     */
    public function importTrades(Request $request, $deploymentId)
    {
        $deployment = StrategyDeployment::findOrFail($deploymentId);

        $data = $request->validate([
            'trades' => 'required|array|min:1|max:1000',
            'trades.*.instrument' => 'required|string',
            'trades.*.side' => 'required|in:BUY,SELL',
            'trades.*.entry_price' => 'required|numeric',
            'trades.*.exit_price' => 'nullable|numeric',
            'trades.*.quantity' => 'required|numeric',
            'trades.*.pnl' => 'nullable|numeric',
            'trades.*.pnl_pips' => 'nullable|numeric',
            'trades.*.status' => 'required|in:PENDING,OPEN,CLOSED,CANCELLED',
            'trades.*.entry_time' => 'required|date',
            'trades.*.exit_time' => 'nullable|date',
            'trades.*.slippage' => 'nullable|numeric',
        ]);

        $imported = 0;
        foreach ($data['trades'] as $tradeData) {
            $tradeData['strategy_deployment_id'] = $deployment->id;
            $tradeData['deployment_phase'] = $deployment->phase;
            $tradeData['strategy_name'] = $deployment->strategy_name;
            $tradeData['user_id'] = auth()->id() ?? 1;  // system user

            // Determine winner
            if (isset($tradeData['pnl'])) {
                $tradeData['is_winner'] = $tradeData['pnl'] > 0;
            }

            Trade::create($tradeData);
            $imported++;
        }

        // Mettre a jour les stats agregees du deployment
        $this->recalculateMetrics($deployment);

        return response()->json([
            'imported' => $imported,
            'total_trades' => $deployment->fresh()->trades_total,
        ]);
    }

    /**
     * POST /api/deployments/{id}/metrics
     * Met a jour les KPIs d'une strategie (Sharpe, DD, etc.)
     */
    public function updateMetrics(Request $request, $deploymentId)
    {
        $deployment = StrategyDeployment::findOrFail($deploymentId);

        $data = $request->validate([
            'pnl_total' => 'nullable|numeric',
            'trades_total' => 'nullable|integer|min:0',
            'trades_won' => 'nullable|integer|min:0',
            'trades_lost' => 'nullable|integer|min:0',
            'max_drawdown' => 'nullable|numeric|min:0|max:100',
            'current_dd' => 'nullable|numeric|min:0|max:100',
            'metrics' => 'nullable|array',
        ]);

        $deployment->update($data);

        return response()->json(['status' => 'updated', 'id' => $deployment->id]);
    }

    /**
     * GET /api/strategies
     * Dashboard: toutes les strategies avec leurs stats
     */
    public function index()
    {
        $strategies = StrategyDeployment::where('status', 'active')
            ->withCount('trades')
            ->get()
            ->groupBy('strategy_name')
            ->map(function ($deployments) {
                $latest = $deployments->sortByDesc('deployed_at')->first();
                return [
                    'name' => $latest->strategy_name,
                    'version' => $latest->version,
                    'phase' => $latest->phase,
                    'pnl' => $deployments->sum('pnl_total'),
                    'trades' => $deployments->sum('trades_total'),
                    'wins' => $deployments->sum('trades_won'),
                    'losses' => $deployments->sum('trades_lost'),
                    'win_rate' => $deployments->sum('trades_total') > 0
                        ? round($deployments->sum('trades_won') / $deployments->sum('trades_total') * 100, 1)
                        : 0,
                    'max_dd' => $deployments->max('max_drawdown'),
                    'current_dd' => $latest->current_dd,
                    'deployed_at' => $latest->deployed_at,
                    'trades_history' => $latest->trades()->orderBy('entry_time', 'desc')->take(20)->get(),
                ];
            })->values();

        return response()->json($strategies);
    }

    /**
     * GET /api/strategies/{strategy}/timeline
     * Cycle de vie complet d'une strategie
     */
    public function timeline($strategyName)
    {
        $deployments = StrategyDeployment::byStrategy($strategyName)
            ->orderBy('deployed_at')
            ->get()
            ->map(function ($dep) {
                return [
                    'phase' => $dep->phase,
                    'status' => $dep->status,
                    'version' => $dep->version,
                    'date' => $dep->deployed_at,
                    'pnl' => $dep->pnl_total,
                    'trades' => $dep->trades_total,
                    'win_rate' => $dep->winRate(),
                ];
            });

        return response()->json($deployments);
    }

    /**
     * GET /api/deployments/{id}
     * Detail d'une strategie
     */
    public function show($id)
    {
        $deployment = StrategyDeployment::with([
            'trades' => fn($q) => $q->orderBy('entry_time', 'desc')->take(50)
        ])->findOrFail($id);

        return response()->json($deployment);
    }

    private function recalculateMetrics(StrategyDeployment $deployment)
    {
        $trades = $deployment->trades()->where('status', 'CLOSED')->get();

        $deployment->update([
            'trades_total' => $trades->count(),
            'trades_won' => $trades->where('is_winner', true)->count(),
            'trades_lost' => $trades->where('is_winner', false)->count(),
            'pnl_total' => $trades->sum('pnl'),
        ]);
    }
}
