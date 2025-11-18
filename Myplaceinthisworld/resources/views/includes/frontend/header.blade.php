<header id="header" class="header d-flex align-items-center sticky-top">
  <div class="header-container container-fluid container-xl position-relative d-flex align-items-center justify-content-end">

    <a href="{{ route('home') }}" class="logo d-flex align-items-center me-auto">
      <img src="{{ asset('assets/img/logo.png') }}" alt="My Place In This World">
      <!-- <h1 class="sitename">My Place In This World</h1> -->
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a></li>
        <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a></li>
        <li><a href="{{ route('gallery') }}" class="{{ request()->routeIs('gallery') ? 'active' : '' }}">Gallery of Growth</a></li>
        <li><a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? 'active' : '' }}">Support</a></li>
        <li><a href="{{ route('membership.index') }}" class="{{ request()->routeIs('membership.*') ? 'active' : '' }}">Membership</a></li>
        @auth
        <li><a href="{{ route('divisions-of-learning.index') }}" class="{{ request()->routeIs('divisions-of-learning.*') ? 'active' : '' }}">Divisions of Learning</a></li>
        @endauth
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>

    <ul class="auth-menu">
      @auth
      @if(auth()->user()->hasRole('administrator'))
      <li>
        <a href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>
      </li>
      @endif
      @if(auth()->user()->is_owner && auth()->user()->school && auth()->user()->school->users()->where('is_owner', false)->count() > 0)
      <li>
        <a href="{{ route('sub-accounts.select') }}"><i class="bi bi-people"></i> 
          @if(auth()->user()->current_sub_account_id)
            Switch Profile
          @else
            Profiles
          @endif
        </a>
      </li>
      @endif
      <li>
        <a href="{{ route('profile.index') }}"><i class="bi bi-person-circle"></i> Profile</a>
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
