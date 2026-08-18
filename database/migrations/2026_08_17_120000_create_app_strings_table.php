<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_strings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('locale', 8);
            $table->text('value');
            $table->timestamps();

            $table->unique(['key', 'locale']);
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_strings');
    }
};
