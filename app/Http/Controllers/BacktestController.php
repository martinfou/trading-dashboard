<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class BacktestController extends Controller
{
    private string $resultsDir;

    public function __construct()
    {
        $this->resultsDir = storage_path('app/backtest-results');
    }

    /**
     * Liste tous les rapports de backtest disponibles.
     */
    public function index()
    {
        File::ensureDirectoryExists($this->resultsDir);

        $reports = [];
        $jsonFiles = File::glob($this->resultsDir . '/*_report.json');

        foreach ($jsonFiles as $jsonPath) {
            try {
                $data = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);
                $strategyName = $data['strategyName'] ?? basename($jsonPath, '_report.json');
                $htmlPath = str_replace('_report.json', '_report.html', $jsonPath);
                $csvPath = str_replace('_report.json', '_trades.csv', $jsonPath);

                $reports[] = [
                    'strategyName' => $strategyName,
                    'period' => $data['period'] ?? ['from' => '—', 'to' => '—'],
                    'metrics' => $data['metrics'] ?? [],
                    'tradesCount' => count($data['trades'] ?? []),
                    'hasHtml' => File::exists($htmlPath),
                    'hasCsv' => File::exists($csvPath),
                    'jsonFile' => basename($jsonPath),
                    'htmlFile' => basename($htmlPath),
                    'createdAt' => date('Y-m-d H:i', File::lastModified($jsonPath)),
                ];
            } catch (\Exception $e) {
                // Skip malformed reports
                continue;
            }
        }

        // Trier par date de création (plus récent d'abord)
        usort($reports, fn($a, $b) => strcmp($b['createdAt'], $a['createdAt']));

        return Inertia::render('Backtest/Index', [
            'reports' => $reports,
        ]);
    }

    /**
     * Affiche un rapport spécifique (lecture du JSON + HTML).
     */
    public function show(string $strategy)
    {
        File::ensureDirectoryExists($this->resultsDir);

        // Chercher un fichier JSON correspondant
        $jsonFiles = File::glob($this->resultsDir . '/' . $strategy . '_report.json');
        if (empty($jsonFiles)) {
            // Fallback: chercher par nom partiel
            $jsonFiles = File::glob($this->resultsDir . '/*' . $strategy . '*.json');
        }

        if (empty($jsonFiles)) {
            abort(404, 'Rapport introuvable pour la stratégie: ' . $strategy);
        }

        $jsonPath = $jsonFiles[0];
        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);

        // Vérifier si le HTML existe
        $htmlFile = str_replace('_report.json', '_report.html', $jsonPath);
        $htmlContent = null;
        if (File::exists($htmlFile)) {
            $htmlContent = File::get($htmlFile);
        }

        return Inertia::render('Backtest/Show', [
            'report' => $data,
            'strategyName' => $data['strategyName'] ?? $strategy,
            'htmlContent' => $htmlContent,
        ]);
    }

    /**
     * Déclenche un nouveau backtest via le moteur trading-bridge avancé.
     *
     * Remplace l'ancien moteur oanda-backtest par BacktestEngine + Monte Carlo
     * + Walk-Forward + PerformanceMetrics du module trading-bridge.
     */
    public function run()
    {
        $bridgeDir = '/home/martinfou/projects/trading-bridge';
        $scriptPath = $bridgeDir . '/scripts/dashboard-bridge.sh';

        File::ensureDirectoryExists($this->resultsDir);

        if (!File::exists($scriptPath)) {
            return back()->withErrors(['error' => 'Bridge script introuvable: ' . $scriptPath]);
        }

        // Exécuter le bridge script avec --sync pour copier dans storage
        $process = new Process([
            'bash', $scriptPath, '--sample', '--sync'
        ], $bridgeDir, null, null, 300);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return back()->withErrors(['error' => 'Le backtest bridge a échoué: ' . $process->getErrorOutput()]);
        }

        return redirect()->route('backtest.index')
            ->with('success', 'Backtest exécuté via trading-bridge engine avancé (Monte Carlo + Walk-Forward + Metrics) !');
    }
}
