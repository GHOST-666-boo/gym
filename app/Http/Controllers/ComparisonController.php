<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    /**
     * Display the comparison page with selected products.
     */
    public function index(Request $request): View
    {
        $productIds = $request->get('products', []);
        
        // Ensure we have an array and limit to maximum 4 products
        if (is_string($productIds)) {
            $productIds = explode(',', $productIds);
        }
        
        $productIds = array_slice(array_filter($productIds), 0, 4);
        
        $products = collect();
        
        if (!empty($productIds)) {
            $products = Product::with('category')
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');
            
            // Maintain the order of products as requested
            $orderedProducts = collect();
            foreach ($productIds as $id) {
                if ($products->has($id)) {
                    $orderedProducts->push($products->get($id));
                }
            }
            $products = $orderedProducts;
        }
        
        return view('public.products.compare', compact('products'));
    }

    /**
     * Add a product to comparison (AJAX).
     */
    public function add(Request $request): JsonResponse
    {
        $productId = $request->get('product_id');
        
        if (!$productId) {
            return response()->json(['error' => 'Product ID is required'], 400);
        }
        
        $product = Product::find($productId);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        
        // Get current comparison list from session
        $comparison = session()->get('comparison', []);
        
        // Check if product is already in comparison
        if (in_array($productId, $comparison)) {
            return response()->json(['error' => 'Product already in comparison'], 400);
        }
        
        // Check if comparison is full (max 4 products)
        if (count($comparison) >= 4) {
            return response()->json(['error' => 'Maximum 4 products can be compared'], 400);
        }
        
        // Add product to comparison
        $comparison[] = $productId;
        session()->put('comparison', $comparison);
        
        return response()->json([
            'success' => true,
            'message' => 'Product added to comparison',
            'count' => count($comparison),
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image_path ? asset('storage/' . $product->image_path) : null
            ]
        ]);
    }

    /**
     * Remove a product from comparison (AJAX).
     */
    public function remove(Request $request): JsonResponse
    {
        $productId = $request->get('product_id');
        
        if (!$productId) {
            return response()->json(['error' => 'Product ID is required'], 400);
        }
        
        // Get current comparison list from session
        $comparison = session()->get('comparison', []);
        
        // Remove product from comparison
        $comparison = array_values(array_filter($comparison, function($id) use ($productId) {
            return $id != $productId;
        }));
        
        session()->put('comparison', $comparison);
        
        return response()->json([
            'success' => true,
            'message' => 'Product removed from comparison',
            'count' => count($comparison)
        ]);
    }

    /**
     * Clear all products from comparison (AJAX).
     */
    public function clear(): JsonResponse
    {
        session()->forget('comparison');
        
        return response()->json([
            'success' => true,
            'message' => 'Comparison cleared',
            'count' => 0
        ]);
    }

    /**
     * Get comparison count (AJAX).
     */
    public function count(): JsonResponse
    {
        $comparison = session()->get('comparison', []);
        
        return response()->json([
            'count' => count($comparison)
        ]);
    }

    /**
     * Get comparison products (AJAX).
     */
    public function products(): JsonResponse
    {
        $comparison = session()->get('comparison', []);
        
        if (empty($comparison)) {
            return response()->json(['products' => []]);
        }
        
        $products = Product::with('category')
            ->whereIn('id', $comparison)
            ->get()
            ->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'short_description' => $product->short_description,
                    'image' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                    'category' => $product->category ? $product->category->name : null,
                    'url' => route('products.show', $product)
                ];
            });
        
        return response()->json(['products' => $products]);
    }
}