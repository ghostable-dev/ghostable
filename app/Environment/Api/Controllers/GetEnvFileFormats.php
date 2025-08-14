<?php

namespace App\Environment\Api\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Environment\Api\Resources\EnvFileFormatResource;
use App\Environment\Enums\EnvFileFormat;
use Illuminate\Http\Resources\Json\JsonResource;

class GetEnvFileFormats extends Controller
{
    public function __invoke(): JsonResource
    {
        return EnvFileFormatResource::collection(EnvFileFormat::cases());
    }
}
