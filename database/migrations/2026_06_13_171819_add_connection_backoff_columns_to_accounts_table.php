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
            $table->unsignedSmallInteger('connection_failure_count')->default(0)->after('automation_dispatched_at');
            $table->timestamp('connection_retry_after')->nullable()->after('connection_failure_count');
            $table->timestamp('last_connection_error_at')->nullable()->after('last_error_message');
            $table->text('last_connection_error_message')->nullable()->after('last_connection_error_at');

            $table->index(['is_active', 'is_archived', 'connection_retry_after'], 'accounts_connection_retry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex('accounts_connection_retry_idx');
            $table->dropColumn([
                'connection_failure_count',
                'connection_retry_after',
                'last_connection_error_at',
                'last_connection_error_message',
            ]);
        });
    }
};
