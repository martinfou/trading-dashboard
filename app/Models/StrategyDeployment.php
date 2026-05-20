<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategyDeployment extends Model
{
    protected $table = 'strategy_deployments';

    protected $fillable = [
        'strategy_name', 'version', 'phase', 'status',
        'git_tag', 'git_commit', 'validation_checks', 'metrics',
        'pnl_total', 'trades_total', 'trades_won', 'trades_lost',
        'max_drawdown', 'current_dd', 'notes', 'deployed_at', 'promoted_at'
    ];

    protected $casts = [
        'validation_checks' => 'array',
        'metrics' => 'array',
        'deployed_at' => 'datetime',
        'promoted_at' => 'datetime',
        'pnl_total' => 'decimal:2',
        'max_drawdown' => 'decimal:2',
        'current_dd' => 'decimal:2',
    ];

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class, 'strategy_deployment_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function winRate(): float
    {
        if ($this->trades_total === 0) return 0;
        return round($this->trades_won / $this->trades_total * 100, 1);
    }

    public function lossRate(): float
    {
        if ($this->trades_total === 0) return 0;
        return round($this->trades_lost / $this->trades_total * 100, 1);
    }

    public function avgPnl(): float
    {
        if ($this->trades_total === 0) return 0;
        return round($this->pnl_total / $this->trades_total, 2);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->phase === 'live';
    }

    // ─── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePhase($query, $phase)
    {
        return $query->where('phase', $phase);
    }

    public function scopeByStrategy($query, $name)
    {
        return $query->where('strategy_name', $name);
    }
}
