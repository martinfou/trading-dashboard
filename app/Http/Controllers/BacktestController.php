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
     * Déclenche un nouveau backtest.
     */
    public function run()
    {
        $projectDir = '/home/martinfou/projects/oanda-strategies/mvn-project';
        $jarPath = $projectDir . '/target/oanda-backtest-1.0.0.jar';

        // Vérifier que le jar existe ou compiler
        if (!File::exists($jarPath)) {
            $this->buildMavenProject($projectDir);
        }

        File::ensureDirectoryExists($this->resultsDir);

        // Exécuter le jar
        $process = new Process([
            'java', '-jar', $jarPath
        ], $projectDir, null, null, 300);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            return back()->withErrors(['error' => 'Le backtest a échoué: ' . $process->getErrorOutput()]);
        }

        // Copier les fichiers JSON/HTML dans storage
        $sourceDir = $projectDir . '/_bmad-output/implementation-artifacts/backtest-reports';
        if (File::exists($sourceDir)) {
            foreach (File::files($sourceDir) as $file) {
                $ext = $file->getExtension();
                if (in_array($ext, ['json', 'html', 'csv'])) {
                    File::copy($file->getPathname(), $this->resultsDir . '/' . $file->getFilename());
                }
            }
        }

        return redirect()->route('backtest.index')
            ->with('success', 'Backtest exécuté et importé avec succès !');
    }

    private function buildMavenProject(string $projectDir): void
    {
        $process = new Process(['mvn', 'clean', 'package', '-DskipTests'], $projectDir, null, null, 180);
        $process->setTimeout(180);
        $process->run();
    }
}
