@extends('layouts.app')

@section('title', 'Select Profile')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Select Profile</h4>
            </div>
            <div class="card-body">
                <p class="lead">Choose which profile you want to use for this session. Each profile has its own saved progress and preferences.</p>

                <form method="POST" action="{{ route('sub-accounts.switch') }}">
                    @csrf

                    <div class="mb-4">
                        <div class="card border-primary">
                            <div class="card-body">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="sub_account_id" id="owner_profile" value="" {{ !auth()->user()->current_sub_account_id ? 'checked' : '' }}>
                                    <label class="form-check-label w-100" for="owner_profile">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ auth()->user()->name }}</strong>
                                                <br>
                                                <small class="text-muted">School Owner</small>
                                            </div>
                                            <span class="badge bg-primary">Owner</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($subAccounts->count() > 0)
                        <h5 class="mb-3">Educator Profiles</h5>
                        @foreach($subAccounts as $subAccount)
                            <div class="mb-3">
                                <div class="card {{ auth()->user()->current_sub_account_id == $subAccount->id ? 'border-success' : '' }}">
                                    <div class="card-body">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="sub_account_id" id="sub_account_{{ $subAccount->id }}" value="{{ $subAccount->id }}" {{ auth()->user()->current_sub_account_id == $subAccount->id ? 'checked' : '' }}>
                                            <label class="form-check-label w-100" for="sub_account_{{ $subAccount->id }}">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $subAccount->name }}</strong>
                                                        <br>
                                                        <small class="text-muted">{{ $subAccount->email }}</small>
                                                    </div>
                                                    <span class="badge bg-info">Educator</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No educator profiles available. You can add educators from the Teachers section.
                        </div>
                    @endif

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Continue</button>
                        <a href="{{ route('divisions-of-learning.index') }}" class="btn btn-outline-secondary">Skip for now</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

