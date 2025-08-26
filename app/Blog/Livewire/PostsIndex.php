<?php

namespace App\Blog\Livewire;

use App\Blog\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class PostsIndex extends Component
{
    use WithPagination;
    
    public function render()
    {
        $featured = Post::published()
            ->where('is_featured', true)
            ->latest()
            ->take(3)
            ->get();

        $posts = Post::published()
            ->where('is_featured', false)
            ->latest()
            ->paginate(6);

        return view('blog.livewire.posts-index')
            ->layout('components.layouts.blog')
            ->with([
                'featuredPosts' => $featured,
                'posts' => $posts,
            ]);
    }
}
