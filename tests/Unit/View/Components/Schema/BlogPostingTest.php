<?php

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use App\View\Components\Schema\BlogPosting;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

uses(TestCase::class);

it('generates blog posting schema', function () {
    Model::setConnectionResolver(new class implements ConnectionResolverInterface
    {
        public function connection($name = null)
        {
            return new class
            {
                public function getQueryGrammar()
                {
                    return new class
                    {
                        public function getDateFormat()
                        {
                            return 'Y-m-d H:i:s';
                        }
                    };
                }
            };
        }

        public function getDefaultConnection()
        {
            return 'test';
        }

        public function setDefaultConnection($name): void {}
    });

    $post = new Post([
        'meta_title' => 'Title',
        'meta_description' => 'Desc',
        'meta_keywords' => ['tag1', 'tag2'],
        'posted_at' => '2025-01-01',
        'category' => PostCategory::PRODUCT_UPDATES,
    ]);

    Route::get('/blog/{post}')->name('blog.view');

    $component = new BlogPosting($post);

    expect($component->render())->toContain('BlogPosting');
});
