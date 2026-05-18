<?php
namespace Database\Seeders;

use App\Models\Trade;
use App\Models\User;
use Carbon\Carbon;

class TradeSeeder extends \Illuminate\Database\Seeder
{
    public function run()
    {
        $user = User::first();
        if (!$user) return;
        
        $trades = [
            ['EUR_USD', 'BUY', 1.0850, 1.0920, 1.0820, 1.0950, 0.01, 7.0, 'CLOSED', 'SMA Crossover', 75, '2026-05-10 08:00', '2026-05-11 14:00'],
            ['GBP_USD', 'SELL', 1.2650, 1.2580, 1.2680, 1.2520, 0.01, 7.0, 'CLOSED', 'Breakout Strategy', 80, '2026-05-12 10:00', '2026-05-12 18:00'],
            ['USD_CAD', 'SELL', 1.3720, 1.3680, 1.3750, 1.3650, 0.01, 4.0, 'OPEN', 'IPC Canada Trade', 90, '2026-05-19 08:20', null],
            ['AUD_USD', 'BUY', 0.7100, null, 0.7070, 0.7160, 0.01, 0, 'PENDING', 'RBA Minutes Long', 80, '2026-05-19 01:20', null],
            ['GBP_JPY', 'SELL', 211.45, null, 211.70, 210.95, 0.01, 0, 'PENDING', 'UK CPI + Japan GDP', 85, '2026-05-20 06:00', null],
        ];
        
        foreach ($trades as $t) {
            Trade::create([
                'user_id' => $user->id,
                'instrument' => $t[0], 'side' => $t[1],
                'entry_price' => $t[2], 'exit_price' => $t[3],
                'stop_loss' => $t[4], 'take_profit' => $t[5],
                'quantity' => $t[6], 'pnl' => $t[7],
                'status' => $t[8], 'strategy_name' => $t[9],
                'confidence' => $t[10],
                'entry_time' => Carbon::parse($t[11]),
                'exit_time' => $t[12] ? Carbon::parse($t[12]) : null,
            ]);
        }
        
        $this->command->info("✅ " . count($trades) . " demo trades created");
    }
}
