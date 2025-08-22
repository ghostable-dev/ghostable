<?php

namespace App\Blog\Livewire;

use App\Blog\Models\Post;
use Livewire\Component;

class PostShow extends Component
{
    public Post $post;
    
    public function render()
    {
        return view('blog.livewire.post-show')
            ->layout('components.layouts.blog');
    }
}
