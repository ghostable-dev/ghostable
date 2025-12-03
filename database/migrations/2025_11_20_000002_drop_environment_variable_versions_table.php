<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('environment_variable_versions');
    }

    public function down(): void
    {
        Schema::create('environment_variable_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('environment_variable_id')->index();
            $table->string('key');
            $table->text('value');
            $table->boolean('is_commented')->default(false);
            $table->unsignedInteger('version');
            $table->uuid('changed_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('environment_variable_id')
                ->references('id')
                ->on('environment_variables')
                ->onDelete('cascade');

            $table->foreign('changed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
