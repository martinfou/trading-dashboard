<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MachineHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HealthCheckController extends Controller
{
    /**
     * POST /api/health/ping
     * Chaque machine envoie son /health à Laravel
     */
    public function ping(Request $request)
    {
        $data = $request->validate([
            'machine' => 'required|string',
            'uptime' => 'nullable|string',
            'version' => 'nullable|string',
            'git_commit' => 'nullable|string',
            'active_strategies' => 'nullable|array',
            'last_trade' => 'nullable|string',
            'errors_24h' => 'nullable|integer|min:0',
            'oanda_api_status' => 'nullable|string',
            'deployment_id' => 'nullable|string',
            'resources' => 'nullable|array',
            'resources.cpu' => 'nullable|numeric',
            'resources.memory' => 'nullable|numeric',
            'resources.disk' => 'nullable|numeric',
        ]);

        $role = $this->detectRole($data['machine']);

        MachineHealth::updateOrCreate(
            ['machine_name' => $data['machine']],
            [
                'role' => $role,
                'status' => 'up',
                'version' => $data['version'] ?? null,
                'git_commit' => $data['git_commit'] ?? null,
                'uptime' => $data['uptime'] ?? null,
                'cpu_percent' => $data['resources']['cpu'] ?? null,
                'memory_percent' => $data['resources']['memory'] ?? null,
                'disk_percent' => $data['resources']['disk'] ?? null,
                'active_strategies' => $data['active_strategies'] ?? [],
                'errors_24h' => $data['errors_24h'] ?? 0,
                'oanda_api_status' => $data['oanda_api_status'] ?? 'unknown',
                'deployment_id' => $data['deployment_id'] ?? null,
                'last_trade' => $data['last_trade'] ?? null,
                'last_health_at' => now(),
                'consecutive_failures' => 0,
            ]
        );

        return response()->json(['status' => 'recorded', 'machine' => $data['machine']]);
    }

    /**
     * GET /api/health
     * Dashboard: voir l'état de toutes les machines
     */
    public function status()
    {
        $machines = MachineHealth::orderBy('role')->get()->map(function ($m) {
            return [
                'name' => $m->machine_name,
                'role' => $m->role,
                'status' => $m->status,
                'color' => $m->color(),
                'uptime' => $m->uptime,
                'cpu' => $m->cpu_percent,
                'memory' => $m->memory_percent,
                'disk' => $m->disk_percent,
                'strategies' => $m->active_strategies,
                'git' => $m->git_commit,
                'last_check' => $m->last_health_at?->diffForHumans(),
                'errors_24h' => $m->errors_24h,
            ];
        });

        $summary = [
            'total' => $machines->count(),
            'up' => $machines->where('status', 'up')->count(),
            'down' => $machines->where('status', 'down')->count(),
            'degraded' => $machines->where('status', 'degraded')->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'machines' => $machines,
        ]);
    }

    // ─── Helpers ───

    private function detectRole(string $hostname): string
    {
        $hostname = strtolower($hostname);
        if (str_contains($hostname, 'backtest')) return 'backtest';
        if (str_contains($hostname, 'paper')) return 'paper';
        if (str_contains($hostname, 'live')) return 'live';
        return 'unknown';
    }
}
