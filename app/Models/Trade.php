<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    protected $fillable = [
        'user_id', 'instrument', 'side', 'entry_price', 'exit_price',
        'stop_loss', 'take_profit', 'quantity', 'pnl', 'pnl_pips',
        'status', 'strategy_name', 'catalyst', 'confidence', 'notes',
        'tags', 'entry_spread', 'is_winner', 'entry_time', 'exit_time',
        'strategy_deployment_id', 'slippage', 'deployment_phase'
    ];

    protected $casts = [
        'tags' => 'array',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'is_winner' => 'boolean',
        'pnl' => 'decimal:2',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function comments(): HasMany { return $this->hasMany(TradeComment::class); }
    public function strategyDeployment(): BelongsTo { return $this->belongsTo(StrategyDeployment::class); }
}
