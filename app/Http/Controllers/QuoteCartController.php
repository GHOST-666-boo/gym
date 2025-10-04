<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\QuoteCartService;
use App\Mail\QuoteRequestReceived;
use App\Mail\QuoteRequestConfirmation;
use App\Mail\QuoteRequestSellerNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Mail;

class QuoteCartController extends Controller
{
    public function __construct(
        private QuoteCartService $quoteCartService
    ) {}

    /**
     * Display the quote cart page.
     */
    public function index(): View
    {
        $cartItems = $this->quoteCartService->getCartItems(auth()->id());
        $cartTotal = $this->quoteCartService->getCartTotal(auth()->id());
        
        return view('quote-cart.index', compact('cartItems', 'cartTotal'));
    }

    /**
     * Add a product to the quote cart.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:999'
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;

        try {
            $this->quoteCartService->addToCart($product, $quantity, auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Product added to quote cart',
                'cart_count' => $this->quoteCartService->getCartCount(auth()->id())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart'
            ], 500);
        }
    }

    /**
     * Remove a product from the quote cart.
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer'
        ]);

        try {
            $this->quoteCartService->removeFromCart($request->product_id, auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Product removed from cart',
                'cart_count' => $this->quoteCartService->getCartCount(auth()->id())
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove product from cart'
            ], 500);
        }
    }

    /**
     * Update quantity of a product in the cart.
     */
    public function updateQuantity(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:0|max:999'
        ]);

        try {
            $this->quoteCartService->updateQuantity(
                $request->product_id, 
                $request->quantity, 
                auth()->id()
            );
            
            $cartTotal = $this->quoteCartService->getCartTotal(auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Quantity updated',
                'cart_count' => $this->quoteCartService->getCartCount(auth()->id()),
                'cart_total' => number_format($cartTotal, 2)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quantity'
            ], 500);
        }
    }

    /**
     * Clear the entire cart.
     */
    public function clear(): JsonResponse
    {
        try {
            $this->quoteCartService->clearCart(auth()->id());
            
            return response()->json([
                'success' => true,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cart'
            ], 500);
        }
    }

    /**
     * Get cart count for AJAX requests.
     */
    public function count(): JsonResponse
    {
        return response()->json([
            'count' => $this->quoteCartService->getCartCount(auth()->id())
        ]);
    }

    /**
     * Submit quote request from cart.
     */
    public function submitQuote(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:2000'
        ]);

        try {
            $quoteRequest = $this->quoteCartService->submitQuoteRequest(
                $request->only(['name', 'email', 'phone', 'company', 'message']),
                auth()->id()
            );

            // Send email notifications
            try {
                // Send confirmation to customer
                Mail::send(new QuoteRequestConfirmation($quoteRequest));
                
                // Send notification to admin
                Mail::send(new QuoteRequestReceived($quoteRequest));
                
                // Send notification to seller
                Mail::send(new QuoteRequestSellerNotification($quoteRequest));
                
            } catch (\Exception $mailException) {
                // Log email error but don't fail the request
                \Log::error('Failed to send quote request emails: ' . $mailException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Quote request submitted successfully',
                'quote_id' => $quoteRequest->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
