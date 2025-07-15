<?php

namespace App\Environment\Actions;

use App\Environment\Entities\PushResultData;
use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ValidateEnvironment;
use Illuminate\Support\Facades\DB;

class PushAndValidateEnvironment
{
    /**
     * Apply the incoming vars, then validate the new state, all in one atomic transaction.
     */
    public function handle(Environment $env, array $incomingRaw): PushResultData
    {
        return DB::transaction(function () use ($env, $incomingRaw) {
            $result = app(PushEnvVars::class)->handle(
                env: $env,
                incomingRaw: $incomingRaw
            );

            // Will throw ValidationException and rollback if invalid
            app(ValidateEnvironment::class)->handle($env);

            return $result;
        });
    }
}
