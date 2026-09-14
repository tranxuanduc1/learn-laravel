@extends('layouts.app')

@section('title', 'My Todos')

@section('content')
    <h1>My Todos</h1>

    @forelse ($todos as $todo)
        <article>
            <h2>{{ $todo->title }}</h2>
            <p>{{ $todo->completed ? 'Completed' : 'Pending' }}</p>
        </article>
    @empty
        <p>You do not have any todos yet.</p>
    @endforelse
@endsection
