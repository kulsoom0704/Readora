@extends('layouts.app')

@section('content')

<h1>Contact Us</h1>

@if(session('success'))

    <div class="card">
        {{ session('success') }}
    </div>

@endif

<div class="card">

    <form method="POST" action="/contact">

        @csrf

        <p>Name</p>
        <input type="text" name="name" required>

        <br><br>

        <p>Email</p>
        <input type="email" name="email" required>

        <br><br>

        <p>Subject</p>
        <input type="text" name="subject" required>

        <br><br>

        <p>Message</p>

        <textarea name="message" rows="6" required></textarea>

        <br><br>

        <button type="submit">
            Send Message
        </button>

    </form>

</div>

@endsection