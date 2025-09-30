<?php

use App\Environment\Variable\Enums\DeliveryMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_variables', function (Blueprint $t) {
            $t->string('delivery_mode', 32)
                ->default(DeliveryMode::STANDARD->value)
                ->after('key')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('environment_variables', function (Blueprint $t) {
            $t->dropColumn('delivery_mode');
        });
    }
};
