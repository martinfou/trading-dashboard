<?php
namespace App\Http\Controllers;

use App\Models\Trade;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class TradingController extends Controller
{
    public function index()
    {
        $trades = Trade::where('user_id', auth()->id())->orderBy('entry_time', 'desc')->limit(20)->get();
        $openTrades = Trade::where('user_id', auth()->id())->where('status', 'OPEN')->get();
        $totalPnl = Trade::where('user_id', auth()->id())->sum('pnl');
        
        return Inertia::render('Trading', [
            'trades' => $trades,
            'openTrades' => $openTrades,
            'totalPnl' => $totalPnl ?? 0,
            'prices' => $this->fetchPrices(),
            'accountBalance' => $this->fetchAccount(),
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

    public function refresh() {
        return response()->json([
            'prices' => $this->fetchPrices(),
            'accountBalance' => $this->fetchAccount(),
        ]);
    }
}
