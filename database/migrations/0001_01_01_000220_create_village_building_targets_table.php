<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_building_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_id');
            $table->unsignedTinyInteger('building_gid')->default(0);
            $table->string('building_type')->nullable();
            $table->unsignedTinyInteger('target_level')->default(0);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['village_id', 'slot_id']);
            $table->index(['village_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_building_targets');
    }
};
