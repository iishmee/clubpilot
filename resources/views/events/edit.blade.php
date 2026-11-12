@extends('layouts.app')

@section('title', 'Edit event proposal · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">Edit event proposal</h1>
    <form method="POST" action="{{ route('events.update', $event) }}">
        @csrf
        @method('PUT')
        @include('events.form-fields')
        <button class="btn btn-primary">Save changes</button>
        <a class="btn btn-outline-secondary" href="{{ route('events.show', $event) }}">Cancel</a>
    </form>
@endsection
