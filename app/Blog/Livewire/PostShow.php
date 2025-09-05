<?php

namespace App\Blog\Livewire;

use App\Blog\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.blog')]
class PostShow extends Component
{
    public Post $post;
    
    public function render()
    {
        return view('blog.livewire.post-show')
            ->layout('components.layouts.blog', ['title' => $this->post->meta_title]);
    }
}
