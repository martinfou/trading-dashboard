<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BacktestRun extends Command
{
    protected $signature = 'backtest:run';
    protected $description = 'Exécute le moteur de backtest Java et importe les résultats';

    private string $jarPath;
    private string $projectDir;
    private string $outputDir;

    public function __construct()
    {
        parent::__construct();
        $this->jarPath = '/home/martinfou/projects/oanda-strategies/mvn-project/target/oanda-backtest-1.0.0.jar';
        $this->projectDir = '/home/martinfou/projects/oanda-strategies/mvn-project';
        $this->outputDir = storage_path('app/backtest-results');
    }

    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════╗');
        $this->info('║   🏛️  Backtest Runner (Laravel → Java)      ║');
        $this->info('╚══════════════════════════════════════════════╝');
        $this->newLine();

        // 1. Vérifier que le jar existe
        if (!File::exists($this->jarPath)) {
            $this->warn('⚠ JAR introuvable, compilation Maven en cours...');
            $buildResult = $this->buildProject();
            if (!$buildResult) {
                $this->error('❌ Échec de la compilation Maven.');
                return Command::FAILURE;
            }
        }

        // 2. Créer le répertoire de sortie
        File::ensureDirectoryExists($this->outputDir);
        $this->line("📁 Répertoire de sortie: {$this->outputDir}");

        // 3. Exécuter le JAR Java
        $this->info('🚀 Lancement du moteur de backtest Java...');
        $this->newLine();

        $process = new Process([
            'java', '-jar', $this->jarPath
        ], $this->projectDir, null, null, 300);

        $process->setTimeout(300);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        if (!$process->isSuccessful()) {
            $this->error('❌ Le backtest Java a échoué.');
            $this->error($process->getErrorOutput());
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('✅ Backtest Java terminé avec succès !');

        // 4. Copier les fichiers JSON et HTML dans le storage Laravel
        $sourceDir = '/home/martinfou/projects/oanda-strategies/_bmad-output/implementation-artifacts/backtest-reports';
        if (!File::exists($sourceDir)) {
            $this->warn('⚠ Répertoire de rapports non trouvé: ' . $sourceDir);
            // Essayer de trouver les fichiers autre part
            $sourceDir = $this->projectDir . '/_bmad-output/implementation-artifacts/backtest-reports';
            if (!File::exists($sourceDir)) {
                $this->warn('⚠ Aucun rapport trouvé.');
                return Command::SUCCESS;
            }
        }

        $files = File::files($sourceDir);
        $copiedCount = 0;

        foreach ($files as $file) {
            $ext = $file->getExtension();
            if (in_array($ext, ['json', 'html', 'csv'])) {
                $destPath = $this->outputDir . '/' . $file->getFilename();
                File::copy($file->getPathname(), $destPath);
                $copiedCount++;
            }
        }

        $this->line("📦 {$copiedCount} fichiers copiés vers storage.");
        $this->newLine();
        $this->info('✅ Importation terminée !');
        $this->line("📂 Consultez les résultats: php artisan backtest:list");

        return Command::SUCCESS;
    }

    private function buildProject(): bool
    {
        $this->info('📦 Compilation du projet Maven...');

        $process = new Process([
            'mvn', 'clean', 'package', '-DskipTests'
        ], $this->projectDir, null, null, 180);

        $process->setTimeout(180);
        $process->run(function ($type, $buffer) {
            if ($type === Process::OUT) {
                echo $buffer;
            }
        });

        return $process->isSuccessful();
    }
}
