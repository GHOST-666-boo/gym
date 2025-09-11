<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * Display a listing of newsletter subscribers.
     */
    public function index(Request $request): View
    {
        $query = NewsletterSubscriber::query();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        // Search by email or name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Sort by subscription date (newest first by default)
        $sortBy = $request->get('sort', 'subscribed_at');
        $sortDirection = $request->get('direction', 'desc');
        
        $allowedSorts = ['email', 'name', 'subscribed_at', 'unsubscribed_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $subscribers = $query->paginate(20)->withQueryString();

        // Get statistics
        $stats = [
            'total' => NewsletterSubscriber::count(),
            'active' => NewsletterSubscriber::active()->count(),
            'inactive' => NewsletterSubscriber::inactive()->count(),
            'recent' => NewsletterSubscriber::where('subscribed_at', '>=', now()->subDays(30))->count(),
        ];

        return view('admin.newsletter.index', compact('subscribers', 'stats'));
    }

    /**
     * Show the form for creating a new subscriber.
     */
    public function create(): View
    {
        return view('admin.newsletter.create');
    }

    /**
     * Store a newly created subscriber.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email',
            'name' => 'nullable|string|max:255',
        ]);

        NewsletterSubscriber::create($validated);

        return redirect()->route('admin.newsletter.index')
            ->with('success', 'Subscriber added successfully.');
    }

    /**
     * Display the specified subscriber.
     */
    public function show(NewsletterSubscriber $subscriber): View
    {
        return view('admin.newsletter.show', compact('subscriber'));
    }

    /**
     * Show the form for editing the specified subscriber.
     */
    public function edit(NewsletterSubscriber $subscriber): View
    {
        return view('admin.newsletter.edit', compact('subscriber'));
    }

    /**
     * Update the specified subscriber.
     */
    public function update(Request $request, NewsletterSubscriber $subscriber): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:newsletter_subscribers,email,' . $subscriber->id,
            'name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $subscriber->update($validated);

        return redirect()->route('admin.newsletter.index')
            ->with('success', 'Subscriber updated successfully.');
    }

    /**
     * Remove the specified subscriber.
     */
    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return redirect()->route('admin.newsletter.index')
            ->with('success', 'Subscriber deleted successfully.');
    }

    /**
     * Bulk actions for subscribers.
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'subscribers' => 'required|array',
            'subscribers.*' => 'exists:newsletter_subscribers,id',
        ]);

        $subscriberIds = $request->subscribers;
        $action = $request->action;

        switch ($action) {
            case 'activate':
                NewsletterSubscriber::whereIn('id', $subscriberIds)->update([
                    'is_active' => true,
                    'unsubscribed_at' => null,
                ]);
                $message = 'Selected subscribers have been activated.';
                break;

            case 'deactivate':
                NewsletterSubscriber::whereIn('id', $subscriberIds)->update([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ]);
                $message = 'Selected subscribers have been deactivated.';
                break;

            case 'delete':
                NewsletterSubscriber::whereIn('id', $subscriberIds)->delete();
                $message = 'Selected subscribers have been deleted.';
                break;
        }

        return redirect()->route('admin.newsletter.index')
            ->with('success', $message);
    }

    /**
     * Export subscribers to CSV.
     */
    public function export(Request $request)
    {
        $query = NewsletterSubscriber::query();

        // Apply same filters as index
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->inactive();
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $subscribers = $query->orderBy('subscribed_at', 'desc')->get();

        $filename = 'newsletter_subscribers_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($subscribers) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, ['Email', 'Name', 'Status', 'Subscribed At', 'Unsubscribed At']);
            
            // Add data rows
            foreach ($subscribers as $subscriber) {
                fputcsv($file, [
                    $subscriber->email,
                    $subscriber->name ?: '',
                    $subscriber->is_active ? 'Active' : 'Inactive',
                    $subscriber->subscribed_at->format('Y-m-d H:i:s'),
                    $subscriber->unsubscribed_at ? $subscriber->unsubscribed_at->format('Y-m-d H:i:s') : '',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
