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
        Schema::table('memberships', function (Blueprint $table) {
            $table->enum('billing_period', ['monthly', 'annual'])->nullable()->after('membership_type');
            $table->string('stripe_subscription_id')->nullable()->after('expires_at');
            $table->string('stripe_customer_id')->nullable()->after('stripe_subscription_id');
            $table->timestamp('purchased_at')->nullable()->after('stripe_customer_id');
            $table->timestamp('renewal_date')->nullable()->after('purchased_at');
            
            $table->index('stripe_subscription_id');
            $table->index('stripe_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn([
                'billing_period',
                'stripe_subscription_id',
                'stripe_customer_id',
                'purchased_at',
                'renewal_date'
            ]);
        });
    }
};
