<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('trade_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trade_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('type')->default('note'); // note, lesson, tag
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('trade_comments'); }
};
