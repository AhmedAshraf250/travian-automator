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
        Schema::create('account_proxies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('scheme', 16)->default('http');
            $table->string('host');
            $table->unsignedSmallInteger('port');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('status', 24)->default('active');
            $table->unsignedInteger('position')->default(1);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_failed_at')->nullable();
            $table->timestamp('cooldown_until')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'status', 'cooldown_until']);
            $table->index(['account_id', 'position']);
        });

        Schema::table('accounts', function (Blueprint $table): void {
            $table->foreignId('active_account_proxy_id')
                ->nullable()
                ->after('proxy_password')
                ->constrained('account_proxies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('active_account_proxy_id');
        });

        Schema::dropIfExists('account_proxies');
    }
};
