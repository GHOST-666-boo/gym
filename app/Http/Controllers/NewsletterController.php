<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use App\Http\Requests\NewsletterSubscriptionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * Subscribe to newsletter.
     */
    public function subscribe(NewsletterSubscriptionRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        $name = $request->validated('name');

        // Check if already subscribed
        $existingSubscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($existingSubscriber) {
            if ($existingSubscriber->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already subscribed to our newsletter.'
                ], 422);
            } else {
                // Reactivate subscription
                $existingSubscriber->resubscribe();
                return response()->json([
                    'success' => true,
                    'message' => 'Welcome back! Your newsletter subscription has been reactivated.'
                ]);
            }
        }

        // Create new subscription
        NewsletterSubscriber::create([
            'email' => $email,
            'name' => $name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for subscribing to our newsletter!'
        ]);
    }

    /**
     * Unsubscribe from newsletter.
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (!$subscriber) {
            return view('newsletter.unsubscribe', [
                'success' => false,
                'message' => 'Invalid unsubscribe link.'
            ]);
        }

        if (!$subscriber->is_active) {
            return view('newsletter.unsubscribe', [
                'success' => true,
                'message' => 'You are already unsubscribed from our newsletter.',
                'already_unsubscribed' => true
            ]);
        }

        $subscriber->unsubscribe();

        return view('newsletter.unsubscribe', [
            'success' => true,
            'message' => 'You have been successfully unsubscribed from our newsletter.'
        ]);
    }

    /**
     * Show newsletter preferences (for future enhancement).
     */
    public function preferences(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->first();

        if (!$subscriber) {
            abort(404, 'Subscriber not found.');
        }

        return view('newsletter.preferences', compact('subscriber'));
    }
}
