<?php

declare(strict_types=1);

namespace App\Api\V1\Http\Controllers\Environment;

use App\Api\Core\Resources\Environment\EnvFileFormatResource;
use App\Core\Http\Controllers\Controller;
use App\Environment\Enums\EnvFileFormat;
use Illuminate\Http\Resources\Json\JsonResource;

final class GetEnvFileFormats extends Controller
{
    public function __invoke(): JsonResource
    {
        return EnvFileFormatResource::collection(EnvFileFormat::cases());
    }
}
