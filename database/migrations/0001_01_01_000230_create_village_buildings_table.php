<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_buildings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_id');
            $table->unsignedTinyInteger('building_gid')->default(0);
            $table->string('building_type')->nullable();
            $table->unsignedTinyInteger('current_level')->default(0);
            $table->boolean('is_under_construction')->default(false);
            $table->boolean('automation_enabled')->default(true);
            $table->timestamp('finish_at')->nullable();
            $table->timestamps();

            $table->unique(['village_id', 'slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_buildings');
    }
};
