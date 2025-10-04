<?php

namespace App\Services;

use App\Models\Product;
use App\Models\QuoteCart;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;

class QuoteCartService
{
    /**
     * Add a product to the quote cart.
     */
    public function addToCart(Product $product, int $quantity = 1, ?int $userId = null): bool
    {
        $sessionId = $userId ? null : Session::getId();
        
        // Check if product already exists in cart
        $existingItem = QuoteCart::where('product_id', $product->id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->first();

        if ($existingItem) {
            // Update quantity
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity
            ]);
        } else {
            // Create new cart item
            QuoteCart::create([
                'session_id' => $sessionId,
                'user_id' => $userId,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
                'product_data' => [
                    'name' => $product->name,
                    'image' => $product->image_path,
                    'category' => $product->category?->name,
                    'slug' => $product->slug,
                ]
            ]);
        }

        return true;
    }

    /**
     * Remove a product from the quote cart.
     */
    public function removeFromCart(int $productId, ?int $userId = null): bool
    {
        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::where('product_id', $productId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->delete() > 0;
    }

    /**
     * Update quantity of a product in the cart.
     */
    public function updateQuantity(int $productId, int $quantity, ?int $userId = null): bool
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($productId, $userId);
        }

        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::where('product_id', $productId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->update(['quantity' => $quantity]) > 0;
    }

    /**
     * Get all items in the quote cart.
     */
    public function getCartItems(?int $userId = null): Collection
    {
        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::with('product')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get the total count of items in the cart.
     */
    public function getCartCount(?int $userId = null): int
    {
        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->sum('quantity');
    }

    /**
     * Get the total estimated value of the cart.
     */
    public function getCartTotal(?int $userId = null): float
    {
        $items = $this->getCartItems($userId);
        return $items->sum(fn($item) => $item->quantity * $item->price);
    }

    /**
     * Clear the entire cart.
     */
    public function clearCart(?int $userId = null): bool
    {
        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->delete() > 0;
    }

    /**
     * Check if a product is in the cart.
     */
    public function isInCart(int $productId, ?int $userId = null): bool
    {
        $sessionId = $userId ? null : Session::getId();
        
        return QuoteCart::where('product_id', $productId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($sessionId, fn($q) => $q->where('session_id', $sessionId))
            ->exists();
    }

    /**
     * Submit a quote request from cart items.
     */
    public function submitQuoteRequest(array $customerData, ?int $userId = null): QuoteRequest
    {
        $cartItems = $this->getCartItems($userId);
        
        if ($cartItems->isEmpty()) {
            throw new \Exception('Cart is empty');
        }

        // Prepare products data
        $products = $cartItems->map(function ($item) {
            return [
                'id' => $item->product_id,
                'name' => $item->product_data['name'] ?? $item->product->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->quantity * $item->price,
                'image' => $item->product_data['image'] ?? null,
                'category' => $item->product_data['category'] ?? null,
                'slug' => $item->product_data['slug'] ?? null,
            ];
        })->toArray();

        // Create quote request
        $quoteRequest = QuoteRequest::create([
            'name' => $customerData['name'],
            'email' => $customerData['email'],
            'phone' => $customerData['phone'] ?? null,
            'company' => $customerData['company'] ?? null,
            'message' => $customerData['message'] ?? null,
            'products' => $products,
            'total_amount' => $this->getCartTotal($userId),
            'status' => 'pending',
        ]);

        // Clear the cart after successful submission
        $this->clearCart($userId);

        return $quoteRequest;
    }

    /**
     * Merge session cart with user cart (for when user logs in).
     */
    public function mergeSessionCartWithUser(int $userId): void
    {
        $sessionId = Session::getId();
        
        $sessionItems = QuoteCart::forSession($sessionId)->get();
        
        foreach ($sessionItems as $sessionItem) {
            $existingUserItem = QuoteCart::where('user_id', $userId)
                ->where('product_id', $sessionItem->product_id)
                ->first();

            if ($existingUserItem) {
                // Merge quantities
                $existingUserItem->update([
                    'quantity' => $existingUserItem->quantity + $sessionItem->quantity
                ]);
            } else {
                // Move session item to user
                $sessionItem->update([
                    'user_id' => $userId,
                    'session_id' => null
                ]);
            }
        }

        // Clean up any remaining session items
        QuoteCart::forSession($sessionId)->delete();
    }
}