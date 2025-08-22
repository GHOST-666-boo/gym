<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use App\Services\AnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    /**
     * The contact service instance.
     */
    protected ContactService $contactService;
    
    /**
     * The analytics service instance.
     */
    protected AnalyticsService $analyticsService;

    /**
     * Create a new controller instance.
     */
    public function __construct(ContactService $contactService, AnalyticsService $analyticsService)
    {
        $this->contactService = $contactService;
        $this->analyticsService = $analyticsService;
    }

    /**
     * Display the contact form.
     */
    public function show(): View
    {
        return view('public.contact');
    }

    /**
     * Handle contact form submission.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            
            // Sanitize the contact data
            $contactData = $this->contactService->sanitizeContactData($validated);

            // Attempt to send the contact form email
            $emailSent = $this->contactService->handleContactSubmission($contactData);
            
            // Track contact submission for analytics
            $this->analyticsService->trackContactSubmission($contactData, $emailSent, $request);

            if ($emailSent) {
                return redirect()->back()
                    ->with('success', 'Thank you for your message, ' . $contactData['name'] . '! We have received your inquiry and will get back to you within 24 hours.');
            } else {
                return redirect()->back()
                    ->with('error', 'We apologize, but there was a technical issue sending your message. Please try again in a few minutes, or contact us directly at ' . config('mail.from.address') . '.')
                    ->withInput();
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Contact form submission error', [
                'error' => $e->getMessage(),
                'input' => $request->except(['_token']),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip()
            ]);

            return redirect()->back()
                ->with('error', 'An unexpected error occurred while processing your message. Please try again or contact us directly.')
                ->withInput();
        }
    }
}