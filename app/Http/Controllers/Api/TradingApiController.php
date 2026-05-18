<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TradingApiController extends Controller
{
    private function oanda($endpoint, $params = [])
    {
        $base = 'https://api-fxpractice.oanda.com/v3/accounts/';
        $account = config('services.oanda.account_id');
        $token = config('services.oanda.token');

        $url = $base . $account . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $r = Http::withToken($token)->get($url);
        if ($r->successful()) {
            return $r->json();
        }
        return null;
    }

    private function fetchPrices()
    {
        $pairs = ['EUR_USD', 'GBP_USD', 'USD_CAD', 'AUD_USD', 'GBP_JPY', 'USD_CHF'];
        $data = $this->oanda('/pricing', ['instruments' => implode(',', $pairs)]);

        if (!$data || !isset($data['prices'])) {
            return [];
        }

        $prices = [];
        foreach ($data['prices'] as $p) {
            $bid = (float)$p['bids'][0]['price'];
            $ask = (float)$p['asks'][0]['price'];
            $prices[$p['instrument']] = [
                'bid' => $bid,
                'ask' => $ask,
                'spread' => round($ask - $bid, 5),
                'status' => $p['status'] ?? 'tradeable',
            ];
        }

        return $prices;
    }

    public function prices()
    {
        return response()->json($this->fetchPrices());
    }

    public function refresh()
    {
        $prices = $this->fetchPrices();
        $accountData = $this->oanda('/summary');
        $tradesData = $this->oanda('/trades');

        $account = ['balance' => '—', 'pl' => 0, 'trades' => 0];
        if ($accountData && isset($accountData['account'])) {
            $a = $accountData['account'];
            $account = [
                'balance' => $a['balance'] ?? '—',
                'pl' => $a['unrealizedPL'] ?? 0,
                'trades' => $a['openTradeCount'] ?? 0,
                'nav' => $a['NAV'] ?? '—',
            ];
        }

        $openTrades = [];
        if ($tradesData && isset($tradesData['trades'])) {
            foreach ($tradesData['trades'] as $t) {
                $openTrades[] = [
                    'id' => $t['id'],
                    'instrument' => $t['instrument'],
                    'side' => $t['currentUnits'] > 0 ? 'BUY' : 'SELL',
                    'units' => abs($t['currentUnits']),
                    'entry_price' => $t['price'],
                    'pnl' => $t['unrealizedPL'] ?? 0,
                ];
            }
        }

        return response()->json([
            'prices' => $prices,
            'accountBalance' => $account,
            'openTrades' => $openTrades,
        ]);
    }
}
