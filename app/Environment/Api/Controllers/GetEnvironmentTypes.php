<?php

namespace App\Environment\Api\Controllers;

use App\Environment\Api\Resources\EnvironmentTypeResource;
use App\Environment\Enums\EnvironmentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\JsonResource;

class GetEnvironmentTypes extends Controller
{
    public function __invoke(): JsonResource
    {
        return EnvironmentTypeResource::collection(EnvironmentType::cases());
    }
}
