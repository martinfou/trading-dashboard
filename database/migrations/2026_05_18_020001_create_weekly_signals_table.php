<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('weekly_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('week_label'); // e.g. "Semaine 18-24 mai"
            $table->string('instrument');
            $table->string('direction'); // LONG/SHORT
            $table->decimal('entry_price', 10, 5);
            $table->decimal('stop_loss', 10, 5);
            $table->decimal('take_profit', 10, 5);
            $table->integer('priority'); // 1, 2, 3
            $table->string('catalyst');
            $table->text('analysis');
            $table->string('status')->default('PENDING');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('weekly_signals'); }
};
