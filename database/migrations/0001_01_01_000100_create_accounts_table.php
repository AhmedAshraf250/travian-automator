<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('server_url');
            $table->string('username');
            $table->text('password');
            $table->string('proxy_scheme', 16)->default('http');
            $table->string('proxy_ip')->nullable();
            $table->unsignedSmallInteger('proxy_port')->nullable();
            $table->string('proxy_username')->nullable();
            $table->text('proxy_password')->nullable();
            $table->foreignId('active_account_proxy_id')->nullable()->constrained('account_proxies')->nullOnDelete();
            $table->text('user_agent')->nullable();
            $table->text('session_cookies')->nullable();
            $table->string('session_transport_fingerprint', 64)->nullable();
            $table->boolean('managed_by_import')->default(true);
            $table->boolean('is_archived')->default(false);
            $table->unsignedInteger('import_position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('paused');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('next_automation_at')->nullable();
            $table->timestamp('automation_dispatched_at')->nullable();
            $table->unsignedSmallInteger('connection_failure_count')->default(0);
            $table->timestamp('connection_retry_after')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamp('last_connection_error_at')->nullable();
            $table->text('last_connection_error_message')->nullable();
            $table->timestamps();

            $table->unique(['server_url', 'username']);
            $table->index(['status', 'is_active']);
            $table->index(['managed_by_import', 'is_archived'], 'accounts_import_visibility_idx');
            $table->index(['managed_by_import', 'is_archived', 'import_position'], 'accounts_import_order_idx');
            $table->index(['is_active', 'is_archived', 'next_automation_at'], 'accounts_automation_due_idx');
            $table->index(['is_active', 'is_archived', 'connection_retry_after'], 'accounts_connection_retry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
