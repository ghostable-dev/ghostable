<?php

use App\Api\Http\Exception\ApiExceptionMap;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

class FakeExceptionHandler implements ExceptionHandler
{
    public array $renderables = [];

    public function report(Throwable $e): void {}

    public function shouldReport(Throwable $e): bool
    {
        return false;
    }

    public function render($request, Throwable $e) {}

    public function renderForConsole($output, Throwable $e) {}

    public function reportable(callable $callback) {}

    public function renderable(callable $callback)
    {
        $this->renderables[] = $callback;
    }
}

function registerMap(): FakeExceptionHandler
{
    $handler = new FakeExceptionHandler;
    app()->instance(ExceptionHandler::class, $handler);
    ApiExceptionMap::register();

    return $handler;
}

it('maps validation exceptions', function () {
    $handler = registerMap();
    [$validation] = $handler->renderables;

    $api = Request::create('/api/v1/test');
    $nonApi = Request::create('/web');

    $response = $validation(ValidationException::withMessages(['f' => ['x']]), $api);
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->status())->toBe(422)
        ->and($response->getData(true)['error']['code'])->toBe('GHO-VAL-0001');

    expect($validation(ValidationException::withMessages([]), $nonApi))->toBeNull();
});

it('maps authentication exceptions', function () {
    $handler = registerMap();
    [, $authn] = $handler->renderables;

    $api = Request::create('/api/v1/test');
    $nonApi = Request::create('/web');

    $response = $authn(new AuthenticationException('nope'), $api);
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->status())->toBe(401)
        ->and($response->getData(true)['error']['code'])->toBe('GHO-AUTH-0001');

    expect($authn(new AuthenticationException('nope'), $nonApi))->toBeNull();
});

it('maps authorization exceptions', function () {
    $handler = registerMap();
    [, , $authz] = $handler->renderables;

    $api = Request::create('/api/v1/test');
    $nonApi = Request::create('/web');

    $response = $authz(new AuthorizationException('nope'), $api);
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->status())->toBe(403)
        ->and($response->getData(true)['error']['code'])->toBe('GHO-AUTHZ-0001');

    expect($authz(new AuthorizationException('nope'), $nonApi))->toBeNull();
});

it('maps model not found exceptions', function () {
    $handler = registerMap();
    [, , , $notFound] = $handler->renderables;

    $api = Request::create('/api/v1/test');
    $nonApi = Request::create('/web');

    $e = tap(new ModelNotFoundException, fn ($ex) => $ex->setModel('Foo'));
    $response = $notFound($e, $api);
    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->status())->toBe(404)
        ->and($response->getData(true)['error']['code'])->toBe('GHO-RES-0001');

    expect($notFound($e, $nonApi))->toBeNull();
});
