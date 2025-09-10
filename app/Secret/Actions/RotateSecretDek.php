<?php

namespace App\Secret\Actions;

use App\Secret\Models\Secret;

class RotateSecretDek
{
    public function handle(Secret $secret): void
    {
        $secret->rotateDek();
    }
}
