<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
