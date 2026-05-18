<?php
namespace App\Console\Commands;

use App\Models\Trade;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckTradingSignals extends Command
{
    protected $signature = 'trading:check';
    protected $description = 'Check trading signals and prices';

    public function handle()
    {
        // Fetch OANDA prices for all pairs
        $pairs = ['EUR_USD','GBP_USD','USD_CAD','AUD_USD','GBP_JPY','USD_CHF'];
        
        try {
            $response = Http::withToken(config('services.oanda.token'))
                ->get('https://api-fxpractice.oanda.com/v3/accounts/'.config('services.oanda.account_id').'/pricing', [
                    'instruments' => implode(',', $pairs)
                ]);
            
            if ($response->successful()) {
                $prices = $response->json()['prices'] ?? [];
                $this->info("📊 Prices fetched: " . count($prices));
                
                foreach ($prices as $p) {
                    $bid = $p['bids'][0]['price'];
                    $ask = $p['asks'][0]['price'];
                    $spread = round((float)$ask - (float)$bid, 5);
                    $this->line("   {$p['instrument']}: {$bid} / {$ask} (spread: {$spread})");
                }
            } else {
                $this->error("OANDA API error: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("Connection error: " . $e->getMessage());
        }
        
        // Check pending trades
        $pending = Trade::where('status', 'PENDING')->get();
        if ($pending->count() > 0) {
            $this->info("\n📋 Pending trades: {$pending->count()}");
            foreach ($pending as $t) {
                $this->line("   {$t->instrument} {$t->side} @ {$t->entry_price} (confidence: {$t->confidence}%)");
            }
        }
        
        return Command::SUCCESS;
    }
}
