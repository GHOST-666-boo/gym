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
        return [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|min:10|max:2000',
        ];
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
        // Trim whitespace from all inputs
        $this->merge([
            'name' => trim($this->input('name', '')),
            'email' => trim(strtolower($this->input('email', ''))),
            'message' => trim($this->input('message', '')),
        ]);
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
            $validated['message'] = strip_tags($validated['message']);
        }
        
        return $validated;
    }
}