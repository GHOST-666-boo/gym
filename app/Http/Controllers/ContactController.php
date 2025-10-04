<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Services\ContactService;
use App\Services\AnalyticsService;
use App\Models\QuoteRequest;
use App\Models\Product;
use App\Mail\QuoteRequestReceived;
use App\Mail\QuoteRequestConfirmation;
use App\Mail\QuoteRequestSellerNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;

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
            
            // Check if this is a quote request (has product_id or product_name)
            if (isset($validated['product_id']) || isset($validated['product_name'])) {
                return $this->handleQuoteRequest($validated);
            }
            
            // Regular contact form handling
            $contactData = $this->contactService->sanitizeContactData($validated);
            $emailSent = $this->contactService->handleContactSubmission($contactData);
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

    /**
     * Handle single product quote request.
     */
    private function handleQuoteRequest(array $validated)
    {
        try {
            // Find product if product_id is provided
            $product = null;
            if (isset($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
            }

            // Prepare product data
            $productData = [];
            if ($product) {
                $productData[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'quantity' => $validated['quantity'] ?? 1,
                    'price' => $product->price,
                    'total' => $product->price * ($validated['quantity'] ?? 1),
                    'category' => $product->category?->name,
                    'slug' => $product->slug,
                ];
            } else {
                // Manual product entry
                $productData[] = [
                    'id' => null,
                    'name' => $validated['product_name'] ?? 'Custom Product',
                    'quantity' => $validated['quantity'] ?? 1,
                    'price' => 0,
                    'total' => 0,
                    'category' => null,
                    'slug' => null,
                ];
            }

            // Create quote request
            $quoteRequest = QuoteRequest::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'company' => $validated['company'] ?? null,
                'message' => $validated['message'] ?? null,
                'products' => $productData,
                'total_amount' => collect($productData)->sum('total'),
                'status' => 'pending',
            ]);

            // Send email notifications
            try {
                // Send confirmation to customer
                Mail::send(new QuoteRequestConfirmation($quoteRequest));
                
                // Send notification to admin
                Mail::send(new QuoteRequestReceived($quoteRequest));
                
                // Send notification to seller
                Mail::send(new QuoteRequestSellerNotification($quoteRequest));
                
            } catch (\Exception $mailException) {
                \Log::error('Failed to send quote request emails: ' . $mailException->getMessage());
            }

            // Check if this is an AJAX request
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your quote request! We have received your inquiry and will get back to you within 24 hours with a detailed quote.',
                    'quote_id' => $quoteRequest->id
                ]);
            }

            return redirect()->back()
                ->with('success', 'Thank you for your quote request! We have received your inquiry and will get back to you within 24 hours with a detailed quote.');

        } catch (\Exception $e) {
            \Log::error('Quote request submission error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $validated,
            ]);

            // Check if this is an AJAX request
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'There was an error sending your quote request. Please try again or contact us directly.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'There was an error sending your quote request. Please try again or contact us directly.')
                ->withInput();
        }
    }
}