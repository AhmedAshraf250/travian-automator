<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('village_troop_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('village_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('unit_id');
            $table->string('order_type')->default('training');
            $table->unsignedInteger('requested_quantity')->default(1);
            $table->unsignedTinyInteger('target_level')->nullable();
            $table->boolean('use_hero_resources')->default(false);
            $table->string('status')->default('scheduled');
            $table->timestamp('execute_after');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedInteger('accepted_quantity')->nullable();
            $table->text('result_message')->nullable();
            $table->timestamps();

            $table->index(['village_id', 'status', 'execute_after'], 'village_troop_orders_village_due_index');
            $table->index(['status', 'execute_after', 'id'], 'village_troop_orders_global_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('village_troop_orders');
    }
};
