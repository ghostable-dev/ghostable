<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;

class UpdateBaseEnvironment
{
    public function handle(Environment $environment, ?Environment $base): void
    {
        $environment->base()->associate($base);
        
        $environment->save();
    }
}
