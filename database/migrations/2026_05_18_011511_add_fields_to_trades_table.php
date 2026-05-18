<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('trades', function (Blueprint $table) {
            $table->json('tags')->nullable();
            $table->decimal('entry_spread', 6, 2)->nullable();
            $table->boolean('is_winner')->nullable();
        });
    }
    public function down(): void {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn(['tags', 'entry_spread', 'is_winner']);
        });
    }
};
