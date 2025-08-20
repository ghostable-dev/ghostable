<?php

declare(strict_types=1);

namespace App\Api\Http\Controllers\Secret;

use App\Api\Resources\Secret\SecretTypeResource;
use App\Core\Http\Controllers\Controller;
use App\Secret\Enums\SecretType;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetSecretTypes extends Controller
{
    public function __invoke(): JsonResource
    {
        return SecretTypeResource::collection(SecretType::cases());
    }
}
