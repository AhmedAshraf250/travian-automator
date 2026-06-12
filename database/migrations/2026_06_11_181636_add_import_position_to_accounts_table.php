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
            $table->unsignedInteger('import_position')->default(0)->after('is_archived');

            $table->index(['managed_by_import', 'is_archived', 'import_position'], 'accounts_import_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropIndex('accounts_import_order_idx');
            $table->dropColumn('import_position');
        });
    }
};
