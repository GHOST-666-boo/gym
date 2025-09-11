<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(ReviewRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();
        
        // Add product_id and user_id to the validated data
        $validated['product_id'] = $product->id;
        $validated['user_id'] = auth()->id(); // Will be null for guests
        
        // Create the review
        Review::create($validated);
        
        return redirect()
            ->route('products.show', $product->slug)
            ->with('success', 'Thank you for your review! It will be published after moderation.');
    }

    /**
     * Display reviews for a specific product (AJAX endpoint).
     */
    public function index(Request $request, Product $product)
    {
        $reviews = $product->approvedReviews()
            ->with(['user:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        if ($request->ajax()) {
            return response()->json([
                'reviews' => $reviews->items(),
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ]
            ]);
        }

        // For non-AJAX requests, redirect to product page
        return redirect()->route('products.show', $product->slug);
    }
}
