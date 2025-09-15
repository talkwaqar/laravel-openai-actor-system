@extends('layouts.app')

@section('title', 'All Actors')

@section('content')
<div id="actor-list-app"
     data-api-url="{{ route('api.actors.index') }}"
     data-csrf-token="{{ csrf_token() }}"
     data-submit-url="{{ route('api.actors.store') }}">
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Loading Actor Management...</p>
        </div>
    </div>
</div>
@endsection
