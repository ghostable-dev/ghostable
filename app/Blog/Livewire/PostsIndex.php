<?php

namespace App\Blog\Livewire;

use App\Blog\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.blog', ['title' => 'Ghostable Blog'])]
class PostsIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $featured = Post::published()
            ->where('is_featured', true)
            ->latest('posted_at')
            ->take(3)
            ->get();

        $posts = Post::published()
            ->where('is_featured', false)
            ->latest('posted_at')
            ->paginate(6);

        return view('blog.livewire.posts-index')
            ->with([
                'featuredPosts' => $featured,
                'posts' => $posts,
            ]);
    }
}
