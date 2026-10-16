<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iap_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 16); // ios | android
            $table->string('product_id', 128);
            $table->string('purchase_token', 512);
            $table->decimal('credit_amount', 10, 2);
            $table->decimal('pay_amount', 10, 2)->nullable();
            $table->string('status', 24)->default('completed'); // completed | failed
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'purchase_token']);
            $table->index(['user_id', 'created_at']);
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_method ENUM(
                'system_bonus',
                'card',
                'terminal',
                'wallet',
                'iap_apple',
                'iap_google'
            ) NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('iap_purchases');

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN payment_method ENUM(
                'system_bonus',
                'card',
                'terminal',
                'wallet'
            ) NOT NULL");
        }
    }
};
