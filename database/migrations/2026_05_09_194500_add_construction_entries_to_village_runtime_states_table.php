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
        Schema::table('village_runtime_states', function (Blueprint $table): void {
            $table->json('construction_entries')->nullable()->after('movement_entries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_runtime_states', function (Blueprint $table): void {
            $table->dropColumn('construction_entries');
        });
    }
};
