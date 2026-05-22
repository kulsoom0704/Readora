@extends('layouts.app')

@section('content')

<h1>Profile Settings</h1>

<div class="card">
    @include('profile.partials.update-profile-information-form')
</div>

<br>

<div class="card">
    @include('profile.partials.update-password-form')
</div>

<br>

<div class="card">
    @include('profile.partials.delete-user-form')
</div>

@endsection