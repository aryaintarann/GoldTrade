<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('direction', ['BUY', 'SELL', 'WAIT']);
            $table->decimal('entry_price', 10, 2)->nullable();
            $table->decimal('stop_loss', 10, 2)->nullable();
            $table->json('take_profits')->nullable();
            $table->unsignedTinyInteger('confidence');
            $table->text('reasoning');
            $table->json('indicators_snapshot');
            $table->json('timeframes_used');
            $table->enum('outcome', ['pending', 'tp_hit', 'sl_hit', 'expired'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signals');
    }
};
