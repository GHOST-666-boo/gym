<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Category;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * The file upload service instance.
     */
    protected FileUploadService $fileUploadService;

    /**
     * Create a new controller instance.
     */
    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }
    /**
     * Display a listing of the products.
     */
    public function index(): View
    {
        $products = Product::with(['category', 'images'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadProductImage($request->file('image'));
                
                if (!$uploadResult['success']) {
                    return redirect()->back()
                        ->with('error', $uploadResult['message'])
                        ->withInput();
                }
                
                $validated['image_path'] = $uploadResult['path'];
            }

            // Remove the image field from validated data as we use image_path
            unset($validated['image']);

            $product = Product::create($validated);

            return redirect()
                ->route('admin.products.index')
                ->with('success', "Product '{$product->name}' has been created successfully.");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create product. Please check your input and try again.')
                ->withInput();
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product->load('category');
        
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $validated = $request->validated();

            // Handle image upload
            if ($request->hasFile('image')) {
                $uploadResult = $this->fileUploadService->uploadProductImage(
                    $request->file('image'), 
                    $product->image_path
                );
                
                if (!$uploadResult['success']) {
                    return redirect()->back()
                        ->with('error', $uploadResult['message'])
                        ->withInput();
                }
                
                $validated['image_path'] = $uploadResult['path'];
            }

            // Remove the image field from validated data as we use image_path
            unset($validated['image']);

            $product->update($validated);

            return redirect()
                ->route('admin.products.index')
                ->with('success', "Product '{$product->name}' has been updated successfully.");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update product. Please check your input and try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $productName = $product->name;
            
            // Delete associated image if it exists
            if ($product->image_path) {
                $this->fileUploadService->deleteFile($product->image_path);
            }

            $product->delete();

            return redirect()
                ->route('admin.products.index')
                ->with('success', "Product '{$productName}' has been deleted successfully.");
                
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Failed to delete product. Please try again.');
        }
    }
}