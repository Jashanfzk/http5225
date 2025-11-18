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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->enum('membership_type', ['primary', 'junior_intermediate']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->enum('billing_period', ['monthly', 'annual']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
            
            $table->index('school_id');
            $table->index('stripe_payment_intent_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
