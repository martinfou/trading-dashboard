<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TradeComment extends Model
{
    protected $fillable = ['trade_id', 'content', 'type'];
    public function trade() { return $this->belongsTo(Trade::class); }
}
