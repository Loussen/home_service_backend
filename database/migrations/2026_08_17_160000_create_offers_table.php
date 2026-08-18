<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type', 20)->default('text')->after('sender_id');
            $table->foreignId('offer_id')->nullable()->after('attachment_url');
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scheduled_at');
            $table->decimal('duration_hours', 4, 1)->nullable();
            $table->decimal('price_azn', 8, 2);
            $table->string('note', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'status']);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreign('offer_id')->references('id')->on('offers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_id');
            $table->dropColumn('type');
        });
        Schema::dropIfExists('offers');
    }
};
