<?php

use App\Environment\Actions\RenderEnvFile;
use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Models\Environment;
use Tests\TestCase;

uses(TestCase::class);

class RenderVar
{
    public function __construct(private array $attributes) {}

    public function __get($key)
    {
        return $this->attributes[$key];
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->attributes, array_flip($keys));
    }
}

it('renders grouped env file with comments and escaped values', function () {
    $env = new Environment;
    $env->file_format = EnvFileFormat::GROUPED_COMMENTS;

    $vars = collect([
        new RenderVar(['key' => 'APP_NAME', 'value' => 'My "App"', 'is_commented' => false]),
        new RenderVar(['key' => 'SECRET_TOKEN', 'value' => 'secret', 'is_commented' => false]),
    ]);

    app()->instance(ResolveEnvironmentVariables::class, new class($vars)
    {
        public function __construct(private $vars) {}

        public function handle($env)
        {
            return $this->vars;
        }
    });

    $content = RenderEnvFile::handle($env, EnvFileFormat::GROUPED_COMMENTS);

    expect($content)->toContain('# APP')
        ->and($content)->toContain('APP_NAME="My \"App\""')
        ->and($content)->toContain('# SECRET');
});
