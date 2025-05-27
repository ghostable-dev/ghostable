<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Api\Resources\EnvironmentTypeResource;
use App\Environment\Enums\EnvironmentType;
use Illuminate\Http\Resources\Json\JsonResource;

class GetEnvironmentTypes extends Controller
{
    public function __invoke(): JsonResource
    {
        return EnvironmentTypeResource::collection(EnvironmentType::cases());
    }
}
