<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineHealth extends Model
{
    protected $table = 'machine_health';

    protected $fillable = [
        'machine_name', 'role', 'status',
        'version', 'git_commit', 'uptime',
        'cpu_percent', 'memory_percent', 'disk_percent',
        'active_strategies', 'errors_24h',
        'oanda_api_status', 'deployment_id', 'last_trade',
        'last_health_at', 'consecutive_failures',
    ];

    protected $casts = [
        'active_strategies' => 'array',
        'last_health_at' => 'datetime',
        'cpu_percent' => 'float',
        'memory_percent' => 'float',
        'disk_percent' => 'float',
    ];

    // ─── Scopes ───

    public function scopeRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }

    // ─── Helpers ───

    public function isHealthy(): bool
    {
        return $this->status === 'up';
    }

    public function markDown(): void
    {
        $this->update([
            'status' => 'down',
            'consecutive_failures' => $this->consecutive_failures + 1,
        ]);
    }

    public function markUp(array $healthData): void
    {
        $this->update(array_merge($healthData, [
            'status' => 'up',
            'consecutive_failures' => 0,
            'last_health_at' => now(),
        ]));
    }

    public function color(): string
    {
        return match ($this->status) {
            'up' => 'green',
            'degraded' => 'orange',
            'down' => 'red',
            default => 'gray',
        };
    }
}
