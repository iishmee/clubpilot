@extends('layouts.app')

@section('title', 'New event proposal · ClubPilot')

@section('content')
    <h1 class="h2 mb-3">New event proposal</h1>
    <form method="POST" action="{{ route('events.store') }}">
        @csrf
        @include('events.form-fields')
        <button class="btn btn-primary">Save draft</button>
        <a class="btn btn-outline-secondary" href="{{ route('events.index') }}">Cancel</a>
    </form>
@endsection
