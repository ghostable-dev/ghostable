<?php

declare(strict_types=1);

namespace App\Api\V1\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\EnvironmentTypeResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvironmentType;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetEnvironmentTypes extends Controller
{
    public function __invoke(): JsonResource
    {
        return EnvironmentTypeResource::collection(EnvironmentType::cases());
    }
}
