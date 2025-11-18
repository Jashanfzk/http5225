<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-end">

    <a href="{{ route('admin.dashboard') }}" class="logo d-flex align-items-center me-auto">
      <img src="{{ asset('assets/img/logo.png') }}" alt="My Place In This World">
      <!-- <h1 class="sitename">My Place In This World</h1> -->
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a></li>
        <li><a href="{{ route('admin.memberships') }}" class="{{ request()->routeIs('admin.memberships') ? 'active' : '' }}">Memberships</a></li>
        <li><a href="{{ route('admin.schools') }}" class="{{ request()->routeIs('admin.schools') ? 'active' : '' }}">Schools</a></li>
        <li><a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">Users</a></li>
        <li><a href="{{ route('admin.content') }}" class="{{ request()->routeIs('admin.content') ? 'active' : '' }}">Content</a></li>
        <li><a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">Settings</a></li>
        <li><a href="{{ route('admin.broken-links') }}" class="{{ request()->routeIs('admin.broken-links') ? 'active' : '' }}">Broken Links</a></li>
        <li><a href="{{ route('admin.activity-log') }}" class="{{ request()->routeIs('admin.activity-log') ? 'active' : '' }}">Activity Log</a></li>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <ul class="auth-menu">
      @auth
      <li>
        <a href="{{ route('profile.index') }}"><i class="bi bi-person-circle"></i> Profile</a>
      </li>
      <li>
        <a href="{{ route('home') }}"><i class="bi bi-house"></i> Frontend</a>
      </li>
      <li>
        <form method="POST" action="{{ route('logout') }}" class="d-inline">
          @csrf
          <button type="submit" class="btn-link" style="background: none; border: none; color: inherit; cursor: pointer; padding: 0;">Logout</button>
        </form>
      </li>
      @else
      <li>
        <a href="{{ route('login') }}">Sign In</a>
      </li>
      <li>
        <a href="{{ route('register') }}" class="btn-register">Register</a>
      </li>
      @endauth
    </ul>

  </div>
</header>
<div class="header-texture">
  <div class="textured-img" style="background-image: url('{{ asset('assets/img/textured-header.svg') }}');"></div>
</div>

