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
        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('server_url');
            $table->string('username');
            $table->text('password');
            $table->string('proxy_ip')->nullable();
            $table->unsignedSmallInteger('proxy_port')->nullable();
            $table->string('proxy_username')->nullable();
            $table->text('proxy_password')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('session_cookies')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('status')->default('paused');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->unique(['server_url', 'username']);
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
