@extends('layouts.app')

@section('content')

<h1>All Users</h1>

@foreach($users as $user)

<div class="card">

    <h2>{{ $user->name }}</h2>

    <p>{{ $user->email }}</p>

    <p>
        Status:

        @if($user->is_admin)
            Admin
        @else
            Regular User
        @endif
    </p>

    @if(!$user->is_admin)

        <a href="/admin/make-admin/{{ $user->id }}">
            Make Admin
        </a>

    @else

        <a href="/admin/remove-admin/{{ $user->id }}">
            Remove Admin
        </a>

    @endif

</div>

@endforeach

@endsection