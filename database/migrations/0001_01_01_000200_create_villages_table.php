<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('travian_village_id');
            $table->string('name');
            $table->integer('x')->nullable();
            $table->integer('y')->nullable();
            $table->unsignedInteger('population')->default(0);
            $table->boolean('is_capital')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'travian_village_id']);
            $table->index(['account_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
