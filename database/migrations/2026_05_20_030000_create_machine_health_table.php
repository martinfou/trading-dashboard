<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('machine_health', function (Blueprint $table) {
            $table->id();
            $table->string('machine_name');           // backtest, paper-vps, live-vps
            $table->string('role');                   // backtest | paper | live
            $table->string('status');                 // up | down | degraded
            $table->string('version')->nullable();
            $table->string('git_commit', 64)->nullable();
            $table->string('uptime')->nullable();
            $table->float('cpu_percent')->nullable();
            $table->float('memory_percent')->nullable();
            $table->float('disk_percent')->nullable();
            $table->json('active_strategies')->nullable();
            $table->integer('errors_24h')->default(0);
            $table->string('oanda_api_status')->nullable();  // ok | error | unknown
            $table->string('deployment_id')->nullable();
            $table->string('last_trade')->nullable();
            $table->timestamp('last_health_at');
            $table->integer('consecutive_failures')->default(0);
            $table->timestamps();

            $table->unique('machine_name');
            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_health');
    }
};
