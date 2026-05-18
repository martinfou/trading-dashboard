<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class TradingController extends Controller
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

    public function index()
    {
        $prices = $this->fetchPrices();
        $account = $this->fetchAccount();
        $openTradesData = $this->oanda('/trades');
        $positionsData = $this->oanda('/openPositions');

        // Format open trades from OANDA
        $openTrades = [];
        if ($openTradesData && isset($openTradesData['trades'])) {
            foreach ($openTradesData['trades'] as $t) {
                $openTrades[] = [
                    'id' => $t['id'],
                    'instrument' => $t['instrument'],
                    'side' => $t['currentUnits'] > 0 ? 'BUY' : 'SELL',
                    'units' => abs($t['currentUnits']),
                    'entry_price' => $t['price'],
                    'current_price' => $prices[$t['instrument']]['bid'] ?? $t['price'],
                    'pnl' => $t['unrealizedPL'] ?? 0,
                    'financing' => $t['financing'] ?? 0,
                    'open_time' => $t['openTime'] ?? null,
                ];
            }
        }

        // Format positions from OANDA
        $positions = [];
        if ($positionsData && isset($positionsData['positions'])) {
            foreach ($positionsData['positions'] as $p) {
                $positions[] = [
                    'instrument' => $p['instrument'],
                    'long_units' => abs($p['long']['units'] ?? 0),
                    'short_units' => abs($p['short']['units'] ?? 0),
                    'long_pl' => $p['long']['unrealizedPL'] ?? 0,
                    'short_pl' => $p['short']['unrealizedPL'] ?? 0,
                ];
            }
        }

        return Inertia::render('Trading', [
            'trades' => [],       // historique vide pour l'instant (pas de transactions enregistrées)
            'openTrades' => $openTrades,
            'positions' => $positions,
            'totalPnl' => (float)($account['pl'] ?? 0),
            'prices' => $prices,
            'accountBalance' => $account,
        ]);
    }

    private function fetchPrices()
    {
        $pairs = ['EUR_USD', 'GBP_USD', 'USD_CAD', 'AUD_USD', 'GBP_JPY', 'USD_CHF'];
        try {
            $data = $this->oanda('/pricing', ['instruments' => implode(',', $pairs)]);
            if ($data && isset($data['prices'])) {
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
        } catch (\Exception $e) {
            // Silently fail, return empty
        }
        return [];
    }

    private function fetchAccount()
    {
        try {
            $data = $this->oanda('/summary');
            if ($data && isset($data['account'])) {
                $a = $data['account'];
                return [
                    'balance' => $a['balance'] ?? '—',
                    'pl' => $a['unrealizedPL'] ?? 0,
                    'trades' => $a['openTradeCount'] ?? 0,
                    'nav' => $a['NAV'] ?? $a['balance'] ?? '—',
                    'marginUsed' => $a['marginUsed'] ?? '0',
                    'marginAvailable' => $a['marginAvailable'] ?? '—',
                    'currency' => $a['currency'] ?? 'CAD',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        return ['balance' => '—', 'pl' => 0, 'trades' => 0, 'nav' => '—', 'marginUsed' => '0', 'marginAvailable' => '—', 'currency' => 'CAD'];
    }

    public function refresh()
    {
        $prices = $this->fetchPrices();
        $account = $this->fetchAccount();

        // Get current open trades for refresh
        $openTradesData = $this->oanda('/trades');
        $openTrades = [];
        if ($openTradesData && isset($openTradesData['trades'])) {
            foreach ($openTradesData['trades'] as $t) {
                $openTrades[] = [
                    'id' => $t['id'],
                    'instrument' => $t['instrument'],
                    'side' => $t['currentUnits'] > 0 ? 'BUY' : 'SELL',
                    'units' => abs($t['currentUnits']),
                    'entry_price' => $t['price'],
                    'current_price' => $prices[$t['instrument']]['bid'] ?? $t['price'],
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
