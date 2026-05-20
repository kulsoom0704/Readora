<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
     <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f3f4f6;
        }

        nav {
            background-color: white;
            padding: 15px 30px;
            border-bottom: 1px solid #ddd;
        }

        nav a {
            margin-right: 20px;
            text-decoration: none;
            color: black;
            font-weight: bold;
        }

        .container {
            padding: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    @include('layouts.navigation')

    <div class="container">
        @yield('content')
    </div>

</body>
</html>
