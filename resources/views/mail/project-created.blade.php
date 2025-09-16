@extends('mail.layouts.simple')

@section('title', "New project created")

@section('preheader')
    New project named {{ $project->name }} created in the {{ $project->organization->name }} organization of Ghostable.
@endsection

@section('content')
    
    <x-mail.simple.paragraph>
        New project named <strong>{{ $project->name }}</strong> created in the <strong>{{ $project->organization->name }}</strong> organization of Ghostable.
    </x-mail.simple.paragraph>
    
    {{-- <x-mail.simple.button href="{{ route('project.environments', ['project' => $project]) }}">View</x-mail.simple.button> --}}

    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection
