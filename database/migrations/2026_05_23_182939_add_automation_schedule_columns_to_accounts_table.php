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
        Schema::table('accounts', function (Blueprint $table): void {
            $table->timestamp('next_automation_at')->nullable()->after('last_sync_at');
            $table->timestamp('automation_dispatched_at')->nullable()->after('next_automation_at');

            $table->index(['is_active', 'is_archived', 'next_automation_at'], 'accounts_automation_due_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex('accounts_automation_due_idx');
            $table->dropColumn(['next_automation_at', 'automation_dispatched_at']);
        });
    }
};
