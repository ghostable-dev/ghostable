@extends('mail.layouts.simple')

@section('title', "Project Deleted")

@section('preheader')
    The project named {{ $project->name }} has been deleted from the {{ $project->organization->name }} organization of Ghostable.
@endsection

@section('content')
    
    <x-mail.simple.paragraph>
        The project named <strong>{{ $project->name }}</strong> has been deleted from the <strong>{{ $project->organization->name }}</strong> organization of Ghostable.
    </x-mail.simple.paragraph>
    
    <x-mail.simple.paragraph>
        You are receiving this alert because you are an administrator of this organization.
    </x-mail.simple.paragraph>

@endsection