<nav x-data="{ mobileOpen: false }" class="main-nav" aria-label="Main navigation">
    <div class="nav-inner">
        <div class="nav-logo">
            <a href="/">Readora</a>
        </div>

        <div class="desktop-nav">
            <a href="/">Home</a>
            <a href="{{ route('books.index') }}">Books</a>
            <a href="/categories">Categories</a>
            <a href="/contact">Contact</a>
            <a href="/faq">FAQ</a>
        </div>

        <div class="desktop-auth">
            @guest
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endguest

            @auth
                <div
                    class="account-dropdown"
                    x-data="{ accountOpen: false }"
                    @click.outside="accountOpen = false"
                    @keydown.escape.stop="accountOpen = false; $refs.accountTrigger.focus()"
                >
                    <button
                        x-ref="accountTrigger"
                        type="button"
                        class="account-nav-trigger"
                        aria-controls="account-menu-desktop"
                        aria-label="Account menu for {{ Auth::user()->name }}"
                        :aria-expanded="accountOpen"
                        @click="accountOpen = !accountOpen"
                        @keydown.arrow-down.prevent="accountOpen = true; $nextTick(() => $refs.profileLink.focus())"
                    >
                        <span>{{ Auth::user()->name }}</span>
                        <svg class="nav-chevron" :class="accountOpen ? 'is-open' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div id="account-menu-desktop" class="account-dropdown-menu" x-show="accountOpen" x-transition style="display: none;">
                        <a x-ref="profileLink" href="{{ route('profile.edit') }}" @keydown.arrow-down.prevent="$refs.reservationsLink.focus()" @keydown.arrow-up.prevent="$refs.accountTrigger.focus()">Edit Profile</a>
                        <a x-ref="reservationsLink" href="{{ route('reservations.index') }}" @keydown.arrow-down.prevent="$refs.nextAccountAction.focus()" @keydown.arrow-up.prevent="$refs.profileLink.focus()">My Reservations</a>

                        @if (Auth::user()->is_admin)
                            <div class="account-menu-divider" aria-hidden="true"></div>
                            <a x-ref="nextAccountAction" href="{{ route('admin.books.index') }}" @keydown.arrow-down.prevent="$refs.adminReservationsLink.focus()" @keydown.arrow-up.prevent="$refs.reservationsLink.focus()">Manage Books</a>
                            <a x-ref="adminReservationsLink" href="{{ route('admin.reservations.index') }}" @keydown.arrow-down.prevent="$refs.adminUsersLink.focus()" @keydown.arrow-up.prevent="$refs.nextAccountAction.focus()">Manage Reservations</a>
                            <a x-ref="adminUsersLink" href="{{ route('admin.users.index') }}" @keydown.arrow-down.prevent="$refs.logoutLink.focus()" @keydown.arrow-up.prevent="$refs.adminReservationsLink.focus()">Manage Users</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="account-logout-form">
                            @csrf
                            <button x-ref="{{ Auth::user()->is_admin ? 'logoutLink' : 'nextAccountAction' }}" type="submit">Log Out</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>

        <button
            x-ref="mobileMenuButton"
            class="mobile-menu-button"
            type="button"
            aria-controls="mobile-navigation-menu"
            :aria-expanded="mobileOpen"
            :aria-label="mobileOpen ? 'Close navigation menu' : 'Open navigation menu'"
            @click="mobileOpen = !mobileOpen"
            @keydown.escape="mobileOpen = false"
        >
            <svg x-show="!mobileOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="mobileOpen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <div
        id="mobile-navigation-menu"
        class="mobile-nav"
        x-show="mobileOpen"
        x-transition
        @click.outside="mobileOpen = false"
        @keydown.escape.window="if (mobileOpen) { mobileOpen = false; $refs.mobileMenuButton.focus() }"
    >
        <a href="/" @click="mobileOpen = false">Home</a>
        <a href="{{ route('books.index') }}" @click="mobileOpen = false">Books</a>
        <a href="/categories" @click="mobileOpen = false">Categories</a>
        <a href="/contact" @click="mobileOpen = false">Contact</a>
        <a href="/faq" @click="mobileOpen = false">FAQ</a>

        @guest
            <a href="{{ route('login') }}" @click="mobileOpen = false">Login</a>
            <a href="{{ route('register') }}" @click="mobileOpen = false">Register</a>
        @endguest

        @auth
            <div
                class="mobile-account-dropdown"
                x-data="{ accountOpen: false }"
                @click.outside="accountOpen = false"
                @keydown.escape.stop="accountOpen = false; $refs.accountTrigger.focus()"
            >
                <button
                    x-ref="accountTrigger"
                    type="button"
                    class="mobile-account-trigger"
                    aria-controls="account-menu-mobile"
                    aria-label="Account menu for {{ Auth::user()->name }}"
                    :aria-expanded="accountOpen"
                    @click="accountOpen = !accountOpen"
                    @keydown.arrow-down.prevent="accountOpen = true; $nextTick(() => $refs.profileLink.focus())"
                >
                    <span>{{ Auth::user()->name }}</span>
                    <svg class="nav-chevron" :class="accountOpen ? 'is-open' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div id="account-menu-mobile" class="mobile-account-links" x-show="accountOpen" x-transition style="display: none;">
                    <a x-ref="profileLink" href="{{ route('profile.edit') }}" @click="mobileOpen = false">Edit Profile</a>
                    <a x-ref="reservationsLink" href="{{ route('reservations.index') }}" @click="mobileOpen = false">My Reservations</a>

                    @if (Auth::user()->is_admin)
                        <div class="account-menu-divider" aria-hidden="true"></div>
                        <a href="{{ route('admin.books.index') }}" @click="mobileOpen = false">Manage Books</a>
                        <a href="{{ route('admin.reservations.index') }}" @click="mobileOpen = false">Manage Reservations</a>
                        <a href="{{ route('admin.users.index') }}" @click="mobileOpen = false">Manage Users</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Log Out</button>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>
