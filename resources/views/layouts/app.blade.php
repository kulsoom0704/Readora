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
            /* padding: 15px 30px; */
            border-bottom: 1px solid #ddd;
        }

        nav a {
            text-decoration: none;
            /* margin-right: 20px;
            text-decoration: none;
            color: black;
            font-weight: bold; */
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
        .book-title {
            font-weight: bold;
            font-size: 28px;
            color: #111827;
}
        .book-title:hover {
            color: #000000;
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
            height: 800px;
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
            max-width: 520px;
        }

        .hero h1 {
            font-size: 56px;
            font-size: 60px;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 20px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .hero-button {
            display: inline-block;
            background: white;
            color: black;
            padding: 14px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: 0.2s ease;
        }
        .hero-button:hover {
            background: #eeeeee;
            transform: translateY(-2px);
    }/* =========================
   NAVIGATION
========================= */

.main-nav {
    background: white;
    border-bottom: 1px solid #ddd;
}

.nav-inner {
    max-width: 1200px;
    margin: 0 auto;
    height: 72px;
    padding: 0 30px;

    display: flex;
    align-items: center;
}

/* Logo */

.nav-logo a {
    color: #111;
    text-decoration: none;
    font-size: 24px;
    font-weight: bold;
}

/* Desktop links */

.desktop-nav {
    display: flex;
    align-items: center;
    gap: 35px;
    margin-left: 70px;
}

.desktop-nav a {
    color: #555;
    text-decoration: none;
    font-size: 17px;
}

.desktop-nav a:hover {
    color: #111;
}

/* Login / Register */

.desktop-auth {
    margin-left: auto;

    display: flex;
    align-items: center;
    gap: 25px;
}

.desktop-auth a {
    color: #111;
    text-decoration: none;
    font-weight: 600;
}

.user-button {
    border: none;
    background: white;
    cursor: pointer;

    display: flex;
    align-items: center;
    gap: 8px;

    font-size: 15px;
}

/* Mobile button */

.mobile-menu-button {
    display: none;

    margin-left: auto;

    border: none;
    background: #f3f4f6;

    border-radius: 8px;

    width: 42px;
    height: 42px;

    font-size: 22px;
    cursor: pointer;
}

/* Mobile menu */

.mobile-nav {
    display: none;
}


/* =========================
   MOBILE
========================= */

@media (max-width: 700px) {

    .nav-inner {
        height: 64px;
        padding: 0 20px;
    }

    .nav-logo a {
        font-size: 22px;
    }

    .desktop-nav,
    .desktop-auth {
        display: none;
    }

    .mobile-menu-button {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .mobile-nav {
        display: flex;
        flex-direction: column;

        background: white;

        padding: 10px 20px 20px;

        border-top: 1px solid #eee;
    }

    .mobile-nav a,
    .mobile-nav button {
        width: 100%;

        padding: 14px 5px;

        text-align: left;

        color: #333;
        text-decoration: none;

        font-size: 16px;

        border: none;
        background: transparent;
        cursor: pointer;
    }

    .mobile-nav a:hover,
    .mobile-nav button:hover {
        background: #f5f5f5;
    }

}
    /* =========================
   HERO - MOBILE
========================= */

@media (max-width: 700px) {

    .hero {
        height: 420px;
        padding: 30px;
        background-position: center;
        border-radius: 15px;
    }

    .hero-text {
        max-width: 100%;
    }

    .hero h1 {
        font-size: 40px;
        line-height: 1.15;
        margin-bottom: 15px;
    }

    .hero p {
        font-size: 17px;
        line-height: 1.5;
        margin-bottom: 25px;
    }

    .hero-button {
        padding: 12px 20px;
    }
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
