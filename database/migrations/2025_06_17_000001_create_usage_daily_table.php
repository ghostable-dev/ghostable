<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_daily', function (Blueprint $table) {
            $table->id();
            $table->string('token');
            $table->string('endpoint');
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();
            $table->unique(['token', 'endpoint', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_daily');
    }
};
