<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Trade extends Model
{
    protected $fillable = ['user_id', 'instrument', 'side', 'entry_price', 'exit_price', 'stop_loss', 'take_profit', 'quantity', 'pnl', 'pnl_pips', 'status', 'strategy_name', 'catalyst', 'confidence', 'notes', 'tags', 'entry_spread', 'is_winner', 'entry_time', 'exit_time'];
    protected $casts = ['tags' => 'array', 'entry_time' => 'datetime', 'exit_time' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }
    public function comments() { return $this->hasMany(TradeComment::class); }
}
