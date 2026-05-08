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
        Schema::create('troop_queues', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->string('troop_type');
            $table->unsignedInteger('quantity');
            $table->string('status')->default('pending');
            $table->timestamp('finish_at')->nullable();
            $table->timestamps();

            $table->index(['village_id', 'status']);
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type');
            $table->string('status')->default('pending');
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'created_at']);
            $table->index(['village_id', 'created_at']);
            $table->index(['activity_type', 'status']);
        });

        Schema::create('import_drafts', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('contents')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_drafts');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('troop_queues');
    }
};
