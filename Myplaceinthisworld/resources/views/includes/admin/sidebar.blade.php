<!-- Admin Sidebar - Optional, can be shown/hidden -->
<div class="admin-sidebar d-none d-lg-block">
  <div class="sidebar-nav">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
          <i class="bi bi-speedometer2"></i> Dashboard
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.memberships') ? 'active' : '' }}" href="{{ route('admin.memberships') }}">
          <i class="bi bi-credit-card"></i> Memberships
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.schools') ? 'active' : '' }}" href="{{ route('admin.schools') }}">
          <i class="bi bi-building"></i> Schools
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}" href="{{ route('admin.users') }}">
          <i class="bi bi-people"></i> Users
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.content') ? 'active' : '' }}" href="{{ route('admin.content') }}">
          <i class="bi bi-file-text"></i> Content
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" href="{{ route('admin.settings') }}">
          <i class="bi bi-gear"></i> Settings
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.broken-links') ? 'active' : '' }}" href="{{ route('admin.broken-links') }}">
          <i class="bi bi-link-45deg"></i> Broken Links
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.activity-log') ? 'active' : '' }}" href="{{ route('admin.activity-log') }}">
          <i class="bi bi-clock-history"></i> Activity Log
        </a>
      </li>
    </ul>
  </div>
</div>

