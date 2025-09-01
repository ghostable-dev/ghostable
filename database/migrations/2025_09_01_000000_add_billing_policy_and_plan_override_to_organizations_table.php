<?php

use App\Billing\Enums\BillingPolicy;
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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('billing_policy', 50)->after('stripe_id')->default(BillingPolicy::RESPECT_SUBSCRIPTION->value);
            $table->string('plan_override', 50)->after('billing_policy')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['billing_policy', 'plan_override']);
        });
    }
};
