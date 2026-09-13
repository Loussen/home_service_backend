<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('source', 32); // admin|request|chat|system|test
            $table->string('type', 64)->default('admin');
            $table->string('title');
            $table->text('body');
            $table->json('payload')->nullable();
            $table->string('audience', 32)->nullable(); // all|clients|providers|selected
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained('service_requests')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->nullOnDelete();
            $table->unsignedInteger('targeted_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index(['type', 'created_at']);
        });

        Schema::create('push_dispatch_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('push_dispatch_id')->constrained('push_dispatches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32); // delivered|skipped_no_token|failed
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['push_dispatch_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_dispatch_recipients');
        Schema::dropIfExists('push_dispatches');
    }
};
