@extends('mail.layouts.simple')

@section('title', $post->title)

@section('preheader')
    {{ $post->title }} - {{ $post->description }}
@endsection

@section('content')

    <x-mail.simple.title size="sm">{{ $post->title }}</x-mail.simple.title>
    
    <x-mail.simple.image :src="Storage::url($post->social)" :alt="$post->title" width="600"/>
    
    <x-mail.simple.paragraph>
        {{ $post->description }}
    </x-mail.simple.paragraph>

    <x-mail.simple.button href="{{ route('blog.view', $post) }}">
        Read the full article →
    </x-mail.simple.button>

@endsection
