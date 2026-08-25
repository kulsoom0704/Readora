<nav x-data="{ open: false }" class="main-nav">

    <div class="nav-inner">

        <!-- Logo -->
        <div class="nav-logo">
            <a href="/">
                Readora
            </a>
        </div>

        <!-- Desktop Navigation -->
        <div class="desktop-nav">

            <a href="/">Home</a>
            <a href="/books">Books</a>
            <a href="/categories">Categories</a>
            <a href="/faq">FAQ</a>
            <a href="/contact">Contact</a>

            @auth
                @if(Auth::user()->is_admin)

                    <a href="/admin/users">
                        Manage Users
                    </a>

                    <a href="{{ route('admin.books.index') }}">
                        Manage Books
                    </a>

                @endif
            @endauth

        </div>

        <!-- Desktop Login / Register -->
        <div class="desktop-auth">

            @guest

                <a href="{{ route('login') }}">
                    Login
                </a>

                <a href="{{ route('register') }}">
                    Register
                </a>

            @endguest


            @auth

                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button class="user-button">

                            <span>
                                {{ Auth::user()->name }}

                                @if(Auth::user()->is_admin)
                                    (Admin)
                                @endif
                            </span>

                            <span>⌄</span>

                        </button>

                    </x-slot>

                    <x-slot name="content">

                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault();
                                this.closest('form').submit();">

                                Log Out

                            </x-dropdown-link>

                        </form>

                    </x-slot>

                </x-dropdown>

            @endauth

        </div>


        <!-- Mobile Hamburger -->
        <button
            class="mobile-menu-button"
            @click="open = !open"
            type="button">

            <span x-show="!open">☰</span>
            <span x-show="open">✕</span>

        </button>

    </div>


    <!-- Mobile Navigation -->
    <div
        class="mobile-nav"
        x-show="open"
        x-transition>

        <a href="/">Home</a>

        <a href="/books">Books</a>

        <a href="/categories">Categories</a>

        <a href="/faq">FAQ</a>

        <a href="/contact">Contact</a>

        @auth

            @if(Auth::user()->is_admin)

                <a href="/admin/users">
                    Manage Users
                </a>

                <a href="{{ route('admin.books.index') }}">
                    Manage Books
                </a>

            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit">
                    Log Out
                </button>

            </form>

        @else

            <a href="{{ route('login') }}">
                Login
            </a>

            <a href="{{ route('register') }}">
                Register
            </a>

        @endauth

    </div>

</nav>