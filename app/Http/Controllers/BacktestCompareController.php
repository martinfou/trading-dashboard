<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class BacktestCompareController extends Controller
{
    private string $resultsDir;

    public function __construct()
    {
        $this->resultsDir = storage_path('app/backtest-results');
    }

    /**
     * Affiche la page de comparaison des stratégies.
     */
    public function index()
    {
        File::ensureDirectoryExists($this->resultsDir);

        $batchFile = $this->resultsDir . '/batch-comparison.json';
        $comparison = null;
        $lastRun = null;

        if (File::exists($batchFile)) {
            $comparison = json_decode(File::get($batchFile), true);
            $lastRun = date('Y-m-d H:i:s', File::lastModified($batchFile));
        }

        return Inertia::render('Backtest/Compare', [
            'comparison' => $comparison,
            'lastRun' => $lastRun,
        ]);
    }

    /**
     * Déclenche un batch backtest complet via trading-bridge.
     * Toutes les stratégies sont testées sur les mêmes données et classées.
     */
    public function run()
    {
        File::ensureDirectoryExists($this->resultsDir);

        $bridgeDir = '/home/martinfou/projects/trading-bridge';
        $dataFile = $bridgeDir . '/data/historical/GBP_JPY_H1.csv';

        // Fallback si le fichier GBP_JPY n'existe plus
        if (!File::exists($dataFile)) {
            $dataFile = $bridgeDir . '/data/historical/dukascopy/gbpusd-h1-bid-2025-01-01-2025-05-19.csv';
        }

        // Run the Java batch backtest via Maven
        // Redirect: classpath/exec plugin → stdout → batch-comparison.json
        $command = sprintf(
            'cd %s && mvn exec:java -pl trading-backtest -q -Dexec.mainClass="com.martinfou.trading.backtest.batch.RunBatchBacktest" -Dexec.args="--data %s --symbol %s --capital 50000" 2>&1 | tail -1',
            escapeshellarg($bridgeDir),
            escapeshellarg($dataFile),
            escapeshellarg('GBP_JPY')
        );

        $output = shell_exec($command);

        if ($output === null || trim($output) === '') {
            return response()->json(['error' => 'Batch backtest failed — no output from Java runner.'], 500);
        }

        // Validate JSON output
        $decoded = json_decode(trim($output), true);
        if ($decoded === null) {
            return response()->json([
                'error' => 'Invalid JSON from batch runner.',
                'raw' => substr($output, 0, 500),
            ], 500);
        }

        // Save results
        File::put($this->resultsDir . '/batch-comparison.json', trim($output));

        return response()->json([
            'success' => true,
            'comparison' => $decoded,
            'lastRun' => date('Y-m-d H:i:s'),
        ]);
    }
}
