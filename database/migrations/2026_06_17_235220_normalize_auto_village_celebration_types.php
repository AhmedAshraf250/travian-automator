<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('village_settings') || ! Schema::hasColumn('village_settings', 'celebration_type')) {
            return;
        }

        DB::table('village_settings')
            ->where('celebration_type', 'auto')
            ->update(['celebration_type' => 'small']);

        Schema::table('village_settings', function (Blueprint $table): void {
            $table->string('celebration_type')->default('small')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
