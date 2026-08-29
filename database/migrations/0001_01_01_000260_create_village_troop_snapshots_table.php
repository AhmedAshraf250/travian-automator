<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_troop_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('units')->nullable();
            $table->json('training_queues')->nullable();
            $table->json('research_queue')->nullable();
            $table->json('smithy_queue')->nullable();
            $table->json('pages')->nullable();
            $table->timestamp('server_reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_troop_snapshots');
    }
};
