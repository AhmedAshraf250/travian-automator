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
        Schema::table('account_proxies', function (Blueprint $table): void {
            $table->unsignedInteger('lifetime_failure_count')->default(0)->after('failure_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_proxies', function (Blueprint $table): void {
            $table->dropColumn('lifetime_failure_count');
        });
    }
};
