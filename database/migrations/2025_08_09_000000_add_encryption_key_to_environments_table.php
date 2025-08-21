<?php

use App\Environment\Models\Environment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->string('encryption_key')->nullable()->after('notifications');
        });

        Environment::query()->whereNull('encryption_key')->each(function (Environment $environment) {
            $environment->encryption_key = base64_encode(Encrypter::generateKey(config('app.cipher')));
            $environment->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn('encryption_key');
        });
    }
};
