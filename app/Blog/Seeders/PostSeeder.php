<?php

namespace App\Blog\Seeders;

use App\Blog\Enums\PostCategory;
use App\Blog\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = json_decode(file_get_contents(base_path('/database/data/sample_posts.json')));
        foreach ($posts as $data) {
            Post::factory()
                ->published()
                ->create([
                    'category' => collect(PostCategory::cases())->random(),
                    'title' => $data->title,
                    'description' => $data->description,
                    'slug' => str($data->title)->slug(),
                    'content' => $data->content,
                    'meta_title' => $data->meta_title,
                    'meta_description' => $data->meta_description,
                    'meta_keywords' => $data->meta_keywords
                ]);
        }
        
        Post::factory()
            ->create([
                'category' => collect(PostCategory::cases())->random(),
                'title' => 'Work In Progress',
                'description' => '',
                'slug' => 'work-in-progress',
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => []
            ]);
            
        Post::factory()
            ->archived()
            ->create([
                'category' => collect(PostCategory::cases())->random(),
                'title' => 'Archived Post',
                'description' => '',
                'slug' => 'archived-post',
                'content' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => []
            ]);
    }
}
