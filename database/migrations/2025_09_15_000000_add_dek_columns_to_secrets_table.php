<?php

use App\Secret\Models\Secret;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->text('dek_wrapped')->nullable()->after('value');
            $table->string('kek_salt')->nullable()->after('dek_wrapped');
        });

        // Migrate existing secrets to per-secret DEKs
        Secret::with(['environment', 'versions'])->each(function (Secret $secret) {
            $environment = $secret->environment;
            if (! $environment || ! $environment->kek_salt) {
                return; // skip if environment missing or no key
            }

            $legacyKek = new Encrypter(base64_decode($environment->kek_salt), 'aes-256-cbc');
            $plaintext = $legacyKek->decryptString($secret->getRawOriginal('value'));

            $kek = $environment->encrypter();
            $dek = base64_encode(Encrypter::generateKey(config('app.cipher')));
            $secret->dek_wrapped = $kek->encryptString($dek);
            $secret->kek_salt = $environment->kek_salt;
            $secret->value = $plaintext; // will be re-encrypted using new DEK via cast
            $secret->save();

            $secret->versions->each(function ($version) use ($legacyKek) {
                $valuePlain = $legacyKek->decryptString($version->getRawOriginal('value'));
                $version->value = $valuePlain; // cast will encrypt with new DEK
                $version->save();
            });
        });
    }

    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->dropColumn(['dek_wrapped', 'kek_salt']);
        });
    }
};
