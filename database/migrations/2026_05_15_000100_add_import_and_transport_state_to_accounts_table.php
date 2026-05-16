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
            $table->boolean('managed_by_import')->default(true)->after('session_cookies');
            $table->boolean('is_archived')->default(false)->after('managed_by_import');
            $table->string('session_transport_fingerprint', 64)->nullable()->after('session_cookies');

            $table->index(['managed_by_import', 'is_archived'], 'accounts_import_visibility_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex('accounts_import_visibility_idx');
            $table->dropColumn([
                'managed_by_import',
                'is_archived',
                'session_transport_fingerprint',
            ]);
        });
    }
};
