<?php

declare(strict_types=1);

use App\Licensing\Enums\LicenseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('purchaser_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plan')->index();
            $table->string('status')->default(LicenseStatus::Active->value)->index();
            $table->string('purchaser_email')->index();
            $table->string('license_key_hash', 64)->unique();
            $table->text('encrypted_license_key')->nullable();
            $table->string('license_key_suffix', 16)->nullable();
            $table->unsignedInteger('seat_count');
            $table->unsignedInteger('activation_limit');
            $table->timestamp('updates_until')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->string('provider_customer_id')->nullable()->index();
            $table->string('provider_checkout_id')->nullable()->index();
            $table->string('provider_subscription_id')->nullable()->index();
            $table->json('provider_metadata')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_checkout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
