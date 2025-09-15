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
        Schema::create('mailing_list_emails', function (Blueprint $table) {
            // UUID primary key
            $table->uuid('id')->primary();

            $table->string('email', 255);
            $table->string('source', 30)->nullable();
            $table->json('sourcePayload')->nullable();
            $table->json('notifications')->nullable();

            $table->softDeletes(); // adds deleted_at
            $table->timestamps();  // adds created_at and updated_at

            // Unique index on (email, deleted_at)
            $table->unique(['email', 'deleted_at'], 'mailing_list_emails_email_deleted_at_unique');

            $table->index('email', 'mailing_list_emails_email_index');
            $table->index('source', 'mailing_list_emails_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailing_list_emails');
    }
};
