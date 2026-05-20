<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class StrategyGenController extends Controller
{
    /**
     * Affiche la page de génération de stratégie.
     */
    public function index()
    {
        return Inertia::render('StrategyGen', [
            'strategyTypes' => [
                ['value' => 'trend',    'label' => 'Trend (Suiveur de tendance)'],
                ['value' => 'meanrev',  'label' => 'Mean Reversion'],
                ['value' => 'breakout', 'label' => 'Breakout'],
                ['value' => 'momentum', 'label' => 'Momentum'],
            ],
            'riskLevels' => [
                ['value' => 'conservative', 'label' => 'Conservateur 🟢'],
                ['value' => 'moderate',     'label' => 'Modéré 🟡'],
                ['value' => 'aggressive',   'label' => 'Agressif 🔴'],
            ],
        ]);
    }

    /**
     * Lance la génération via le GeneticEngine Java.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type'       => 'required|in:trend,meanrev,breakout,momentum',
            'name'       => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
            'riskLevel'  => 'nullable|in:conservative,moderate,aggressive',
            'population' => 'nullable|integer|min:10|max:100',
            'generations'=> 'nullable|integer|min:5|max:100',
        ]);

        $type    = escapeshellarg($validated['type']);
        $name    = escapeshellarg($validated['name']);

        // Construire la commande
        $scriptDir = '/home/martinfou/projects/trading-bridge/scripts';
        $cmd = sprintf(
            'cd /home/martinfou/projects/trading-bridge && ./scripts/export-strategy.sh --type %s --name %s --backtest 2>&1',
            $type,
            $name
        );

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(300); // 5 minutes max
        $process->run();

        $output = $process->getOutput();
        $exitCode = $process->getExitCode();

        return response()->json([
            'success'  => $process->isSuccessful(),
            'exitCode' => $exitCode,
            'output'   => $output,
            'error'    => $process->isSuccessful() ? null : $process->getErrorOutput(),
        ]);
    }
}
