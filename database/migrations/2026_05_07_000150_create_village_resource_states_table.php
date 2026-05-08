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
        Schema::create('village_resource_states', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('wood')->default(0);
            $table->unsignedInteger('clay')->default(0);
            $table->unsignedInteger('iron')->default(0);
            $table->unsignedInteger('crop')->default(0);
            $table->integer('wood_production')->default(0);
            $table->integer('clay_production')->default(0);
            $table->integer('iron_production')->default(0);
            $table->integer('crop_production')->default(0);
            $table->unsignedInteger('warehouse_capacity')->default(0);
            $table->unsignedInteger('granary_capacity')->default(0);
            $table->timestamp('simulated_at')->nullable();
            $table->timestamp('server_reported_at')->nullable();
            $table->timestamps();

            $table->unique('village_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('village_resource_states');
    }
};
