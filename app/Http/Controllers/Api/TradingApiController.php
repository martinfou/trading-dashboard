<?php
namespace App\Http\Controllers\Api;

use App\Models\Trade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TradingApiController extends Controller
{
    public function refresh()
    {
        return response()->json([
            'prices' => $this->fetchPrices(),
            'accountBalance' => $this->fetchAccount(),
        ]);
    }

    public function prices()
    {
        return response()->json($this->fetchPrices());
    }

    public function stats()
    {
        $userId = auth()->id();
        $closed = Trade::where('user_id', $userId)->where('status', 'CLOSED')->get();
        
        $totalTrades = $closed->count();
        $winningTrades = $closed->where('pnl', '>', 0)->count();
        $totalPnl = $closed->sum('pnl');
        $winRate = $totalTrades > 0 ? round($winningTrades / $totalTrades * 100, 1) : 0;
        
        return response()->json([
            'totalTrades' => $totalTrades,
            'winningTrades' => $winningTrades,
            'totalPnl' => $totalPnl,
            'winRate' => $winRate,
        ]);
    }

    private function fetchPrices() {
        $pairs = ['EUR_USD','GBP_USD','USD_CAD','AUD_USD','GBP_JPY','USD_CHF'];
        try {
            $r = Http::withToken(config('services.oanda.token'))
                ->get('https://api-fxpractice.oanda.com/v3/accounts/'.config('services.oanda.account_id').'/pricing', [
                    'instruments' => implode(',', $pairs) ]);
            if ($r->successful()) {
                $prices = [];
                foreach ($r->json()['prices'] ?? [] as $p) {
                    $prices[$p['instrument']] = [
                        'bid' => $p['bids'][0]['price'],
                        'ask' => $p['asks'][0]['price'],
                        'spread' => round((float)$p['asks'][0]['price']-(float)$p['bids'][0]['price'],5)];
                } return $prices;
            }
        } catch (\Exception $e) {}
        return [];
    }

    private function fetchAccount() {
        try {
            $r = Http::withToken(config('services.oanda.token'))
                ->get('https://api-fxpractice.oanda.com/v3/accounts/'.config('services.oanda.account_id').'/summary');
            if ($r->successful()) {
                $a = $r->json()['account'];
                return ['balance' => $a['balance'], 'pl' => $a['unrealizedPL']??0, 'trades' => $a['openTradeCount']??0];
            }
        } catch (\Exception $e) {}
        return ['balance' => '—', 'pl' => 0, 'trades' => 0];
    }
}
