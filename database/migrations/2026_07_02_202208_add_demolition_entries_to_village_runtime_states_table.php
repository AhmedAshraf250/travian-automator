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
        Schema::table('village_runtime_states', function (Blueprint $table) {
            $table->json('demolition_entries')->nullable()->after('construction_entries');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('village_runtime_states', function (Blueprint $table) {
            $table->dropColumn('demolition_entries');
        });
    }
};
