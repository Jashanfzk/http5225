@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

<div class="container py-4">
  <div class="row mb-4">
    <div class="col-12">
      <h1 class="display-5 fw-bold">Admin Dashboard</h1>
      <p class="lead">Welcome back, {{ auth()->user()->name }}!</p>
    </div>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-xl-3 col-lg-6 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="bg-primary bg-opacity-10 rounded p-3">
                <i class="bi bi-building text-primary fs-4"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1">Total Schools</h6>
              <h3 class="mb-0">{{ $totalSchools }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="bg-success bg-opacity-10 rounded p-3">
                <i class="bi bi-people text-success fs-4"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1">Total Users</h6>
              <h3 class="mb-0">{{ $totalUsers }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="bg-info bg-opacity-10 rounded p-3">
                <i class="bi bi-credit-card text-info fs-4"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1">Active Memberships</h6>
              <h3 class="mb-0">{{ $totalMemberships }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-lg-6 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
              <div class="bg-warning bg-opacity-10 rounded p-3">
                <i class="bi bi-exclamation-triangle text-warning fs-4"></i>
              </div>
            </div>
            <div class="flex-grow-1 ms-3">
              <h6 class="text-muted mb-1">Expiring Soon</h6>
              <h3 class="mb-0">{{ $expiringMemberships->count() }}</h3>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Membership Type Breakdown -->
  <div class="row g-4 mb-4">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="bi bi-book"></i> Primary Memberships</h5>
        </div>
        <div class="card-body">
          <h2 class="mb-0">{{ $primaryCount }}</h2>
          <p class="text-muted mb-0">Active subscriptions</p>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <h5 class="mb-0"><i class="bi bi-mortarboard"></i> Junior Intermediate</h5>
        </div>
        <div class="card-body">
          <h2 class="mb-0">{{ $juniorIntermediateCount }}</h2>
          <p class="text-muted mb-0">Active subscriptions</p>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="bi bi-graduation-cap"></i> High School</h5>
        </div>
        <div class="card-body">
          <h2 class="mb-0">{{ $highSchoolCount }}</h2>
          <p class="text-muted mb-0">Active subscriptions</p>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Expiring Memberships -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header bg-warning">
          <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Expiring Memberships (Next 30 Days)</h5>
        </div>
        <div class="card-body">
          @if($expiringMemberships->count() > 0)
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>School</th>
                    <th>Type</th>
                    <th>Expires</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($expiringMemberships as $membership)
                  <tr>
                    <td>{{ $membership->school->school_name ?? 'N/A' }}</td>
                    <td>
                      <span class="badge bg-secondary">
                        {{ ucfirst(str_replace('_', ' ', $membership->membership_type)) }}
                      </span>
                    </td>
                    <td>
                      <span class="text-danger">
                        {{ \Carbon\Carbon::parse($membership->expires_at)->format('M d, Y') }}
                      </span>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-muted mb-0">No memberships expiring in the next 30 days.</p>
          @endif
        </div>
      </div>
    </div>

    <!-- Recent Schools -->
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Schools</h5>
        </div>
        <div class="card-body">
          @if($recentSchools->count() > 0)
            <div class="list-group list-group-flush">
              @foreach($recentSchools as $school)
              <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="mb-1">{{ $school->school_name }}</h6>
                    <small class="text-muted">{{ $school->school_board }}</small>
                  </div>
                  <small class="text-muted">{{ $school->created_at->diffForHumans() }}</small>
                </div>
              </div>
              @endforeach
            </div>
          @else
            <p class="text-muted mb-0">No schools registered yet.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <a href="{{ route('admin.memberships') }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-credit-card"></i> Manage Memberships
              </a>
            </div>
            <div class="col-md-3">
              <a href="{{ route('admin.schools') }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-building"></i> Manage Schools
              </a>
            </div>
            <div class="col-md-3">
              <a href="{{ route('admin.users') }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-people"></i> Manage Users
              </a>
            </div>
            <div class="col-md-3">
              <a href="{{ route('admin.content') }}" class="btn btn-outline-primary w-100">
                <i class="bi bi-file-text"></i> Manage Content
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

@endsection

