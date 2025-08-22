<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Middleware is already applied in routes/web.php for admin routes
    }

    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request): View
    {
        $query = Review::with(['product:id,name,slug', 'user:id,name'])
            ->orderBy('created_at', 'desc');

        // Filter by approval status
        if ($request->has('status')) {
            if ($request->status === 'pending') {
                $query->pending();
            } elseif ($request->status === 'approved') {
                $query->approved();
            }
        }

        // Filter by rating
        if ($request->has('rating') && $request->rating) {
            $query->byRating($request->rating);
        }

        // Search by product name or reviewer name
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reviewer_name', 'LIKE', "%{$search}%")
                  ->orWhere('title', 'LIKE', "%{$search}%")
                  ->orWhereHas('product', function ($productQuery) use ($search) {
                      $productQuery->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $reviews = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Review::count(),
            'pending' => Review::pending()->count(),
            'approved' => Review::approved()->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Display the specified review.
     */
    public function show(Review $review): View
    {
        $review->load(['product:id,name,slug', 'user:id,name,email', 'approvedBy:id,name']);
        
        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve the specified review.
     */
    public function approve(Review $review): RedirectResponse
    {
        $review->approve(auth()->user());

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review has been approved successfully.');
    }

    /**
     * Reject/unapprove the specified review.
     */
    public function reject(Review $review): RedirectResponse
    {
        $review->reject();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', 'Review has been rejected successfully.');
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(Review $review): RedirectResponse
    {
        $productName = $review->product->name;
        $review->delete();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', "Review for '{$productName}' has been deleted successfully.");
    }

    /**
     * Bulk approve selected reviews.
     */
    public function bulkApprove(Request $request): RedirectResponse
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        $reviews = Review::whereIn('id', $request->review_ids)->get();
        
        foreach ($reviews as $review) {
            $review->approve(auth()->user());
        }

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', count($reviews) . ' reviews have been approved successfully.');
    }

    /**
     * Bulk reject selected reviews.
     */
    public function bulkReject(Request $request): RedirectResponse
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        $reviews = Review::whereIn('id', $request->review_ids)->get();
        
        foreach ($reviews as $review) {
            $review->reject();
        }

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', count($reviews) . ' reviews have been rejected successfully.');
    }

    /**
     * Bulk delete selected reviews.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        $count = Review::whereIn('id', $request->review_ids)->count();
        Review::whereIn('id', $request->review_ids)->delete();

        return redirect()
            ->route('admin.reviews.index')
            ->with('success', $count . ' reviews have been deleted successfully.');
    }
}
