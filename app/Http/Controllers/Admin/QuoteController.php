<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class QuoteController extends Controller
{
    /**
     * Display a listing of quote requests.
     */
    public function index(Request $request): View
    {
        $query = QuoteRequest::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('company', 'LIKE', "%{$search}%")
                    ->orWhere('id', 'LIKE', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort by latest first
        $quotes = $query->orderBy('created_at', 'desc')->paginate(20);

        // Statistics
        $stats = [
            'total' => QuoteRequest::count(),
            'pending' => QuoteRequest::where('status', 'pending')->count(),
            'processing' => QuoteRequest::where('status', 'processing')->count(),
            'quoted' => QuoteRequest::where('status', 'quoted')->count(),
            'completed' => QuoteRequest::where('status', 'completed')->count(),
            'recent' => QuoteRequest::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('admin.quotes.index', compact('quotes', 'stats'));
    }

    /**
     * Display the specified quote request.
     */
    public function show(QuoteRequest $quote): View
    {
        return view('admin.quotes.show', compact('quote'));
    }

    /**
     * Update the quote request status.
     */
    public function updateStatus(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,processing,quoted,completed,cancelled'
        ]);

        $quote->update([
            'status' => $request->status,
            'quoted_at' => $request->status === 'quoted' ? now() : $quote->quoted_at,
        ]);

        return redirect()->back()->with('success', 'Quote status updated successfully.');
    }

    /**
     * Add notes or update quote details.
     */
    public function update(Request $request, QuoteRequest $quote): RedirectResponse
    {
        $request->validate([
            'total_amount' => 'nullable|numeric|min:0',
            'expires_at' => 'nullable|date|after:today',
            'admin_notes' => 'nullable|string|max:2000'
        ]);

        $updateData = [];

        if ($request->filled('total_amount')) {
            $updateData['total_amount'] = $request->total_amount;
        }

        if ($request->filled('expires_at')) {
            $updateData['expires_at'] = $request->expires_at;
        }

        if (!empty($updateData)) {
            $quote->update($updateData);
        }

        return redirect()->back()->with('success', 'Quote updated successfully.');
    }

    /**
     * Delete a quote request.
     */
    public function destroy(QuoteRequest $quote)
    {
        $quote->delete();

        // Return JSON response for AJAX requests
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Quote request deleted successfully.'
            ]);
        }

        return redirect()->route('admin.quotes.index')
            ->with('success', 'Quote request deleted successfully.');
    }

    /**
     * Bulk actions for multiple quotes.
     */
    public function bulkAction(Request $request)
    {
        \Log::info('Bulk action request received', [
            'action' => $request->input('action'),
            'quotes' => $request->input('quotes'),
            'all_input' => $request->all()
        ]);

        $request->validate([
            'action' => 'required|in:delete,mark_processing,mark_quoted,mark_completed',
            'quotes' => 'required|array',
            'quotes.*' => 'exists:quote_requests,id'
        ]);

        $quotes = QuoteRequest::whereIn('id', $request->quotes);

        $message = '';

        switch ($request->action) {
            case 'delete':
                $count = $quotes->count();
                $quotes->delete();
                $message = "{$count} quote(s) deleted successfully.";
                break;

            case 'mark_processing':
                $quotes->update(['status' => 'processing']);
                $message = 'Selected quotes marked as processing.';
                break;

            case 'mark_quoted':
                $quotes->update(['status' => 'quoted', 'quoted_at' => now()]);
                $message = 'Selected quotes marked as quoted.';
                break;

            case 'mark_completed':
                $quotes->update(['status' => 'completed']);
                $message = 'Selected quotes marked as completed.';
                break;
        }

        // Return JSON response for AJAX requests
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->back()->with('success', $message);
    }
}
