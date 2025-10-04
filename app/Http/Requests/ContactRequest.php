<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone can submit a contact form
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:2000',
        ];

        // Add quote-specific validation rules if this is a quote request
        if ($this->has('product_id') || $this->has('product_name')) {
            $rules['product_id'] = 'nullable|exists:products,id';
            $rules['product_name'] = 'nullable|string|max:255';
            $rules['quantity'] = 'nullable|integer|min:1|max:999';
            $rules['phone'] = 'nullable|string|max:20';
            $rules['company'] = 'nullable|string|max:255';
            
            // Make message optional for quote requests
            $rules['message'] = 'nullable|string|max:2000';
        } else {
            // For regular contact forms, message is required
            $rules['message'] = 'required|string|min:10|max:2000';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.min' => 'Your name must be at least 2 characters long.',
            'name.max' => 'Your name cannot exceed 255 characters.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Your email address cannot exceed 255 characters.',
            'message.required' => 'Please enter your message.',
            'message.min' => 'Your message must be at least 10 characters long.',
            'message.max' => 'Your message cannot exceed 2,000 characters.',
            'product_id.exists' => 'The selected product is not valid.',
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 999.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'company.max' => 'Company name cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attribute names for validation errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'name',
            'email' => 'email address',
            'message' => 'message',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [
            'name' => trim($this->input('name', '')),
            'email' => trim(strtolower($this->input('email', ''))),
            'message' => trim($this->input('message', '')),
        ];

        // Add quote-specific fields if present
        if ($this->has('product_id')) {
            $data['product_id'] = $this->input('product_id');
        }
        if ($this->has('product_name')) {
            $data['product_name'] = trim($this->input('product_name', ''));
        }
        if ($this->has('quantity')) {
            $data['quantity'] = (int) $this->input('quantity', 1);
        }
        if ($this->has('phone')) {
            $data['phone'] = trim($this->input('phone', ''));
        }
        if ($this->has('company')) {
            $data['company'] = trim($this->input('company', ''));
        }

        $this->merge($data);
    }

    /**
     * Get the validated data with additional processing.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Additional sanitization for security
        if (is_array($validated)) {
            $validated['name'] = strip_tags($validated['name']);
            if (isset($validated['message'])) {
                $validated['message'] = strip_tags($validated['message']);
            }
            if (isset($validated['product_name'])) {
                $validated['product_name'] = strip_tags($validated['product_name']);
            }
            if (isset($validated['company'])) {
                $validated['company'] = strip_tags($validated['company']);
            }
        }
        
        return $validated;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        if ($this->wantsJson()) {
            $response = response()->json([
                'success' => false,
                'message' => 'Validation failed. Please check your input.',
                'errors' => $validator->errors()
            ], 422);

            throw new \Illuminate\Validation\ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}