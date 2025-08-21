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
        return view('blog.livewire.posts-index')
            ->layout('components.layouts.guest')
            ->with([
                'posts' => Post::published()->latest()->paginate(6),
            ]);
    }
}
