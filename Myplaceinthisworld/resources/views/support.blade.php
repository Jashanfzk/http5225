@extends('layouts.app')

@section('title', 'Support')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="display-4 fw-bold mb-4">Support</h1>
        <div class="card shadow-sm">
            <div class="card-body p-5">
                <h3>Need Help?</h3>
                <p>If you have any questions or need assistance, please contact our support team.</p>
                <ul>
                    <li><strong>Email:</strong> support@educationalplatform.com</li>
                    <li><strong>Phone:</strong> 1-800-EDU-HELP</li>
                    <li><strong>Hours:</strong> Monday - Friday, 9 AM - 5 PM EST</li>
                </ul>
                <h4 class="mt-4">Frequently Asked Questions</h4>
                <div class="accordion mt-3" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                How do I access High School resources?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                High School resources are automatically included free with every school registration. Simply register your school and you'll have immediate access.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How do I purchase additional divisions?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Visit the Membership page to purchase access to Primary or Junior Intermediate divisions. Each division costs $399.99 per year.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

