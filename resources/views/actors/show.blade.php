@extends('layouts.app')

@section('title', 'Actor Details')

@section('content')
<div id="actor-detail-app" 
     data-uuid="{{ $uuid }}"
     data-back-url="{{ route('actors.index') }}">
    <div class="flex items-center justify-center min-h-[400px]">
        <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Loading Actor Details...</p>
        </div>
    </div>
</div>
@endsection
