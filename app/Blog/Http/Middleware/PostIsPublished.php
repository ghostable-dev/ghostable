<?php

namespace App\Blog\Http\Middleware;

use App\Blog\Enums\PostStatus;
use Closure;

class PostIsPublished
{
    public function handle($request, Closure $next)
    {
        if (! $request->route('post')->status->is(PostStatus::PUBLISHED)) {
            abort(404);
        }

        return $next($request);
    }
}
