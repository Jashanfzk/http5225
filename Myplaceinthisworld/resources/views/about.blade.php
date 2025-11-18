@extends('layouts.app')

@section('title', 'About Us')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="display-4 fw-bold mb-4">About Us</h1>
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <p class="lead">Welcome to our educational platform, designed to empower schools with comprehensive learning resources.</p>
                <p>Our platform provides access to three main divisions of learning:</p>
                <ul>
                    <li><strong>Primary Division</strong> - Comprehensive resources for primary education</li>
                    <li><strong>Junior Intermediate</strong> - Engaging content for junior intermediate students</li>
                    <li><strong>High School</strong> - Advanced resources for high school education (Free with registration)</li>
                </ul>
                <p>We believe in making quality education accessible to all schools, which is why High School resources are included free with every school registration.</p>
            </div>
        </div>
    </div>
</div>
@endsection

