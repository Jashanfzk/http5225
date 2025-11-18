@extends('layouts.app')

@section('title', 'Membership')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="display-5 fw-bold">Membership Plans</h1>
        <p class="lead">Choose the learning divisions that best fit your school's needs.</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card h-100 shadow {{ $hasPrimary ? 'border-success' : '' }}">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Primary Division</h4>
            </div>
            <div class="card-body">
                @if($hasPrimary && $primaryMembership)
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Active
                        @if($primaryMembership->expires_at)
                            <br><small>Expires: {{ $primaryMembership->expires_at->format('M d, Y') }}</small>
                        @endif
                        @if($primaryMembership->billing_period)
                            <br><small>Billing: {{ ucfirst($primaryMembership->billing_period) }}</small>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <div class="btn-group w-100" role="group" data-toggle="buttons">
                            <input type="radio" class="btn-check" name="primary_billing" id="primary_annual" value="annual" checked>
                            <label class="btn btn-outline-primary" for="primary_annual">
                                Annual<br>
                                <strong>${{ number_format($pricing['primary']['annual'], 2) }}</strong>
                            </label>
                            <input type="radio" class="btn-check" name="primary_billing" id="primary_monthly" value="monthly">
                            <label class="btn btn-outline-primary" for="primary_monthly">
                                Monthly<br>
                                <strong>${{ number_format($pricing['primary']['monthly'], 2) }}</strong>
                            </label>
                        </div>
                    </div>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success"></i> Comprehensive primary resources</li>
                        <li><i class="bi bi-check-circle text-success"></i> Activity library</li>
                        <li><i class="bi bi-check-circle text-success"></i> Progress tracking</li>
                    </ul>
                    <form method="POST" action="{{ route('payment.checkout') }}" id="primary-form">
                        @csrf
                        <input type="hidden" name="membership_type" value="primary">
                        <input type="hidden" name="billing_period" value="annual" id="primary_billing_period">
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm" name="coupon_code" placeholder="Coupon code (optional)">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Purchase Now</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow {{ $hasJuniorIntermediate ? 'border-success' : '' }}">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Junior Intermediate</h4>
            </div>
            <div class="card-body">
                @if($hasJuniorIntermediate && $juniorIntermediateMembership)
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Active
                        @if($juniorIntermediateMembership->expires_at)
                            <br><small>Expires: {{ $juniorIntermediateMembership->expires_at->format('M d, Y') }}</small>
                        @endif
                        @if($juniorIntermediateMembership->billing_period)
                            <br><small>Billing: {{ ucfirst($juniorIntermediateMembership->billing_period) }}</small>
                        @endif
                    </div>
                @else
                    <div class="mb-3">
                        <div class="btn-group w-100" role="group" data-toggle="buttons">
                            <input type="radio" class="btn-check" name="junior_billing" id="junior_annual" value="annual" checked>
                            <label class="btn btn-outline-info" for="junior_annual">
                                Annual<br>
                                <strong>${{ number_format($pricing['junior_intermediate']['annual'], 2) }}</strong>
                            </label>
                            <input type="radio" class="btn-check" name="junior_billing" id="junior_monthly" value="monthly">
                            <label class="btn btn-outline-info" for="junior_monthly">
                                Monthly<br>
                                <strong>${{ number_format($pricing['junior_intermediate']['monthly'], 2) }}</strong>
                            </label>
                        </div>
                    </div>
                    <ul class="list-unstyled">
                        <li><i class="bi bi-check-circle text-success"></i> Junior intermediate content</li>
                        <li><i class="bi bi-check-circle text-success"></i> Activity library</li>
                        <li><i class="bi bi-check-circle text-success"></i> Progress tracking</li>
                    </ul>
                    <form method="POST" action="{{ route('payment.checkout') }}" id="junior-form">
                        @csrf
                        <input type="hidden" name="membership_type" value="junior_intermediate">
                        <input type="hidden" name="billing_period" value="annual" id="junior_billing_period">
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm" name="coupon_code" placeholder="Coupon code (optional)">
                        </div>
                        <button type="submit" class="btn btn-info w-100">Purchase Now</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 shadow border-success">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">High School</h4>
            </div>
            <div class="card-body">
                <h3 class="text-success">Free</h3>
                <p class="text-muted">Included with registration</p>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle text-success"></i> High school resources</li>
                    <li><i class="bi bi-check-circle text-success"></i> Activity library</li>
                    <li><i class="bi bi-check-circle text-success"></i> Progress tracking</li>
                </ul>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Active
                </div>
            </div>
        </div>
    </div>
</div>

@if($memberships->count() > 0)
<div class="row">
    <div class="col-12">
        <h3>Your Memberships</h3>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Status</th>
                        <th>Expires At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($memberships as $membership)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $membership->membership_type)) }}</td>
                        <td>
                            <span class="badge bg-{{ $membership->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($membership->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $membership->expires_at ? $membership->expires_at->format('Y-m-d') : 'Never' }}
                            @if($membership->billing_period)
                                <br><small class="text-muted">{{ ucfirst($membership->billing_period) }}</small>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Handle billing period selection for Primary
    document.querySelectorAll('input[name="primary_billing"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('primary_billing_period').value = this.value;
        });
    });

    // Handle billing period selection for Junior Intermediate
    document.querySelectorAll('input[name="junior_billing"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('junior_billing_period').value = this.value;
        });
    });
</script>
@endpush
@endsection

