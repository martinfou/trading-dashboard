<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('strategy_deployments', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_name');          // TREND_FOLLOWING_1_EURUSD_H1
            $table->string('version');                // v1.0.0
            $table->string('phase');                  // backtest | paper | live | retired
            $table->string('status');                 // pending | active | failed | rolled_back
            $table->string('git_tag')->nullable();    // git tag reference
            $table->string('git_commit', 64);         // SHA du commit
            $table->json('validation_checks');        // {sharpe: 1.8, pf: 2.1, dd: 12.3}
            $table->json('metrics')->nullable();       // {win_rate, sharpe_rolling, profit_factor}
            $table->decimal('pnl_total', 12, 2)->default(0);
            $table->integer('trades_total')->default(0);
            $table->integer('trades_won')->default(0);
            $table->integer('trades_lost')->default(0);
            $table->decimal('max_drawdown', 8, 2)->nullable();
            $table->decimal('current_dd', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('deployed_at');
            $table->timestamp('promoted_at')->nullable();  // phase change
            $table->timestamps();

            // Index pour requetes rapides
            $table->index('strategy_name');
            $table->index('phase');
            $table->index('status');
            $table->unique(['strategy_name', 'version', 'phase']);
        });

        // Link trades to deployment
        Schema::table('trades', function (Blueprint $table) {
            $table->foreignId('strategy_deployment_id')
                  ->nullable()
                  ->constrained('strategy_deployments')
                  ->nullOnDelete();
            $table->decimal('slippage', 8, 3)->nullable();   // slippage reel
            $table->string('deployment_phase')->nullable();   // paper vs live
        });

        // Ajouter is_winner si pas deja la
        if (!Schema::hasColumn('trades', 'is_winner')) {
            Schema::table('trades', function (Blueprint $table) {
                $table->boolean('is_winner')->nullable();
            });
        }
    }

    public function down(): void {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropForeign(['strategy_deployment_id']);
            $table->dropColumn(['strategy_deployment_id', 'slippage', 'deployment_phase']);
        });
        Schema::dropIfExists('strategy_deployments');
    }
};
