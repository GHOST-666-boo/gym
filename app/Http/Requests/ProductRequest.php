<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only authenticated admin users can manage products
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:999999.99',
            'short_description' => 'required|string|max:500',
            'long_description' => 'required|string|max:10000',
            'category_id' => 'nullable|exists:categories,id',
            'stock_quantity' => 'required|integer|min:0|max:999999',
            'low_stock_threshold' => 'required|integer|min:0|max:999999',
            'track_inventory' => 'boolean',
        ];

        // Different image validation rules for create vs update
        if ($this->isMethod('post')) {
            // Creating new product - image is optional but if provided must be valid
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240|dimensions:min_width=300,min_height=300';
        } else {
            // Updating existing product - image is optional
            $rules['image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240|dimensions:min_width=300,min_height=300';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'name.max' => 'The product name cannot exceed 255 characters.',
            'price.required' => 'The product price is required.',
            'price.numeric' => 'The product price must be a valid number.',
            'price.min' => 'The product price cannot be negative.',
            'price.max' => 'The product price cannot exceed $999,999.99.',
            'short_description.required' => 'A short description is required.',
            'short_description.max' => 'The short description cannot exceed 500 characters.',
            'long_description.required' => 'A detailed description is required.',
            'long_description.max' => 'The detailed description cannot exceed 10,000 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be a JPEG, PNG, JPG, GIF, or WebP file.',
            'image.max' => 'The image size cannot exceed 10MB.',
            'image.dimensions' => 'The image must be at least 300x300 pixels.',
            'stock_quantity.required' => 'The stock quantity is required.',
            'stock_quantity.integer' => 'The stock quantity must be a whole number.',
            'stock_quantity.min' => 'The stock quantity cannot be negative.',
            'stock_quantity.max' => 'The stock quantity cannot exceed 999,999.',
            'low_stock_threshold.required' => 'The low stock threshold is required.',
            'low_stock_threshold.integer' => 'The low stock threshold must be a whole number.',
            'low_stock_threshold.min' => 'The low stock threshold cannot be negative.',
            'low_stock_threshold.max' => 'The low stock threshold cannot exceed 999,999.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'price' => 'price',
            'short_description' => 'short description',
            'long_description' => 'detailed description',
            'category_id' => 'category',
            'image' => 'product image',
            'stock_quantity' => 'stock quantity',
            'low_stock_threshold' => 'low stock threshold',
            'track_inventory' => 'inventory tracking',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up price input - remove any currency symbols or commas
        if ($this->has('price')) {
            $price = $this->input('price');
            $price = preg_replace('/[^\d.]/', '', $price);
            $this->merge(['price' => $price]);
        }
    }
}