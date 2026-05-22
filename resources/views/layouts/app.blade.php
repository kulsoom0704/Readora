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
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
}

        .book-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: 0.2s;
            max-width: 300px;
}

        .book-card:hover {
            transform: translateY(-5px);
}

        .book-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
}
        .book-content {
        padding: 15px;
}

        .book-card h2,
        .book-card p {
            padding-left: 15px;
            padding-right: 15px;
    
}

        .book-card h2 {
            margin-top: 15px;
}
.hero {
    height: 80vh;
    background-image: url('/images/homecover.jpg');
    background-size: cover;
    background-position: center;
    border-radius: 20px;
    display: flex;
    align-items: center;
    padding: 60px;
    color: white;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
}

.hero-text {
    position: relative;
    z-index: 2;
    max-width: 500px;
}

.hero h1 {
    font-size: 60px;
    margin-bottom: 20px;
}

.hero p {
    font-size: 20px;
    line-height: 1.6;
    margin-bottom: 30px;
}

.hero-button {
    background: white;
    color: black;
    padding: 14px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
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
