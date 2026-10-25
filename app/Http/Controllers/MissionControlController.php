<?php

namespace App\Http\Controllers;

use App\Models\MachineHealth;
use App\Models\StrategyDeployment;
use Inertia\Inertia;

class MissionControlController extends Controller
{
    /**
     * GET /mission-control
     * Mission Control Dashboard — consolidated view of all machines.
     */
    public function index()
    {
        $machines = MachineHealth::orderBy('role')->get()->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->machine_name,
            'role' => $m->role,
            'status' => $m->status,
            'color' => $m->color(),
            'uptime' => $m->uptime,
            'version' => $m->version,
            'git_commit' => $m->git_commit,
            'cpu' => $m->cpu_percent,
            'memory' => $m->memory_percent,
            'disk' => $m->disk_percent,
            'strategies' => $m->active_strategies ?? [],
            'errors_24h' => $m->errors_24h,
            'oanda_status' => $m->oanda_api_status,
            'last_check' => $m->last_health_at?->diffForHumans(),
            'last_trade' => $m->last_trade,
        ]);

        $summary = [
            'total' => $machines->count(),
            'up' => $machines->where('status', 'up')->count(),
            'down' => $machines->where('status', 'down')->count(),
            'degraded' => $machines->where('status', 'degraded')->count(),
        ];

        $activeStrategies = StrategyDeployment::active()
            ->withCount('trades')
            ->get()
            ->groupBy('strategy_name')
            ->map(fn ($deps) => [
                'name' => $deps->first()->strategy_name,
                'version' => $deps->first()->version,
                'phase' => $deps->first()->phase,
                'status' => $deps->first()->status,
                'pnl' => $deps->sum('pnl_total'),
                'trades' => $deps->sum('trades_total'),
                'wins' => $deps->sum('trades_won'),
                'losses' => $deps->sum('trades_lost'),
                'win_rate' => $deps->sum('trades_total') > 0
                    ? round($deps->sum('trades_won') / $deps->sum('trades_total') * 100, 1)
                    : 0,
                'max_dd' => $deps->max('max_drawdown'),
                'current_dd' => $deps->first()->current_dd,
            ])->values();

        return Inertia::render('MissionControl', [
            'machines' => $machines,
            'summary' => $summary,
            'strategies' => $activeStrategies,
            'refreshUrl' => route('mission-control'),
        ]);
    }
}
