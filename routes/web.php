<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use Illuminate\Support\Facades\Route;

// Public routes with full-page caching
Route::middleware('full.page.cache')->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('home');
    Route::get('/products', [PublicController::class, 'products'])->name('products.index');
    Route::get('/search', [PublicController::class, 'search'])->name('products.search');
    Route::get('/category/{category:slug}', [PublicController::class, 'category'])->name('products.category');
    Route::get('/products/{product:slug}', [PublicController::class, 'show'])->name('products.show');
    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
});

// Contact form (no caching for POST)
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Newsletter routes
Route::post('/newsletter/subscribe', [App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/newsletter/preferences/{token}', [App\Http\Controllers\NewsletterController::class, 'preferences'])->name('newsletter.preferences');

// Review routes
Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index'])->name('reviews.index');

// Product comparison routes
Route::get('/compare', [ComparisonController::class, 'index'])->name('products.compare');
Route::post('/compare/add', [ComparisonController::class, 'add'])->name('products.compare.add');
Route::post('/compare/remove', [ComparisonController::class, 'remove'])->name('products.compare.remove');
Route::post('/compare/clear', [ComparisonController::class, 'clear'])->name('products.compare.clear');
Route::get('/compare/count', [ComparisonController::class, 'count'])->name('products.compare.count');
Route::get('/compare/products', [ComparisonController::class, 'products'])->name('products.compare.products');

// SEO routes with full-page caching
Route::middleware('full.page.cache')->group(function () {
    Route::get('/sitemap.xml', [PublicController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [PublicController::class, 'robots'])->name('robots');
});

// Test syntax route
Route::get('/test-syntax', function () {
    return view('test-syntax');
});

// Test simple product page
Route::get('/test-product-simple', function () {
    $product = App\Models\Product::first();
    $relatedProducts = collect();
    return view('public.products.show', compact('product', 'relatedProducts'));
});



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes for user management
Route::middleware(['auth', 'admin', 'admin.errors'])->prefix('admin')->name('admin.')->group(function () {
    // Admin dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    Route::get('/users/create', [App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])->name('users.store');
    
    // Product management routes
    Route::resource('products', ProductController::class);
    
    // Product image management routes
    Route::post('products/{product}/images', [App\Http\Controllers\Admin\ProductImageController::class, 'store'])->name('products.images.store');
    Route::put('products/{product}/images/{image}', [App\Http\Controllers\Admin\ProductImageController::class, 'update'])->name('products.images.update');
    Route::post('products/{product}/images/order', [App\Http\Controllers\Admin\ProductImageController::class, 'updateOrder'])->name('products.images.order');
    Route::post('products/{product}/images/{image}/primary', [App\Http\Controllers\Admin\ProductImageController::class, 'setPrimary'])->name('products.images.primary');
    Route::delete('products/{product}/images/{image}', [App\Http\Controllers\Admin\ProductImageController::class, 'destroy'])->name('products.images.destroy');
    
    // Category management routes
    Route::resource('categories', CategoryController::class);
    Route::get('categories/{category}/confirm-delete', [CategoryController::class, 'confirmDelete'])->name('categories.confirm-delete');
    Route::post('categories/{category}/reassign-delete', [CategoryController::class, 'reassignAndDelete'])->name('categories.reassign-delete');
    
    // Review management routes
    Route::resource('reviews', AdminReviewController::class)->only(['index', 'show', 'destroy']);
    Route::post('reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('reviews/bulk-approve', [AdminReviewController::class, 'bulkApprove'])->name('reviews.bulk-approve');
    Route::post('reviews/bulk-reject', [AdminReviewController::class, 'bulkReject'])->name('reviews.bulk-reject');
    Route::post('reviews/bulk-delete', [AdminReviewController::class, 'bulkDelete'])->name('reviews.bulk-delete');
    
    // Newsletter management routes
    Route::resource('newsletter', App\Http\Controllers\Admin\NewsletterController::class);
    Route::post('newsletter/bulk-action', [App\Http\Controllers\Admin\NewsletterController::class, 'bulkAction'])->name('newsletter.bulk-action');
    Route::get('newsletter-export', [App\Http\Controllers\Admin\NewsletterController::class, 'export'])->name('newsletter.export');
    
    // Analytics routes
    Route::get('analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/data', [App\Http\Controllers\Admin\AnalyticsController::class, 'data'])->name('analytics.data');
    Route::get('analytics/real-time', [App\Http\Controllers\Admin\AnalyticsController::class, 'realTime'])->name('analytics.real-time');
    Route::get('analytics/export', [App\Http\Controllers\Admin\AnalyticsController::class, 'export'])->name('analytics.export');
    Route::post('analytics/clear-cache', [App\Http\Controllers\Admin\AnalyticsController::class, 'clearCache'])->name('analytics.clear-cache');
    
    // Cache management routes
    Route::get('cache', [App\Http\Controllers\Admin\CacheController::class, 'index'])->name('cache.index');
    Route::post('cache/clear-all', [App\Http\Controllers\Admin\CacheController::class, 'clearAll'])->name('cache.clear-all');
    Route::post('cache/clear-tags', [App\Http\Controllers\Admin\CacheController::class, 'clearTags'])->name('cache.clear-tags');
    Route::post('cache/warm-up', [App\Http\Controllers\Admin\CacheController::class, 'warmUp'])->name('cache.warm-up');
    Route::post('cache/clear-full-page', [App\Http\Controllers\Admin\CacheController::class, 'clearFullPage'])->name('cache.clear-full-page');
    Route::post('cache/clear-images', [App\Http\Controllers\Admin\CacheController::class, 'clearImages'])->name('cache.clear-images');
    Route::get('cache/stats', [App\Http\Controllers\Admin\CacheController::class, 'stats'])->name('cache.stats');
});

require __DIR__.'/auth.php';
