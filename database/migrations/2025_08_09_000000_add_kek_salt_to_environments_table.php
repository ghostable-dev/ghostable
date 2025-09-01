<?php

use App\Environment\Models\Environment;
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
        Schema::table('environments', function (Blueprint $table) {
            $table->string('kek_salt')->nullable()->after('notifications');
        });

        Environment::query()->whereNull('kek_salt')->each(function (Environment $environment) {
            $environment->kek_salt = base64_encode(random_bytes(32));
            $environment->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn('kek_salt');
        });
    }
};
