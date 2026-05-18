<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WeeklySignal extends Model
{
    protected $fillable = ['user_id', 'week_label', 'instrument', 'direction', 'entry_price', 'stop_loss', 'take_profit', 'priority', 'catalyst', 'analysis', 'status'];
    public function user() { return $this->belongsTo(User::class); }
}
