<?php

namespace App\Environment\Deployment\Contracts;

use App\Environment\Models\Environment;

interface SupportsEncryptedDeployment
{
    public function enableEncryption(Environment $environment): void;
}
